<?php
namespace watoki\cqurator\web;

use watoki\collections\Map;
use watoki\cqurator\ActionDispatcher;
use watoki\cqurator\RepresenterRegistry;
use watoki\curir\cookie\Cookie;
use watoki\curir\cookie\CookieStore;
use watoki\curir\protocol\Url;
use watoki\curir\Responder;
use watoki\deli\Request;
use watoki\factory\Factory;

class QueryResource extends ActionResource {

    const TYPE = 'query';
    const LAST_QUERY_COOKIE = 'lastQuery';
    const BREADCRUMB_COOKIE = 'breadcrumbs';

    /** @var \watoki\curir\cookie\CookieStore */
    private $cookies;

    /**
     * @param Factory $factory <-
     * @param ActionDispatcher $dispatcher <-
     * @param RepresenterRegistry $registry <-
     * @param \watoki\curir\cookie\CookieStore $cookies <-
     */
    function __construct(Factory $factory, ActionDispatcher $dispatcher, RepresenterRegistry $registry, CookieStore $cookies) {
        parent::__construct($factory, $registry, $dispatcher);
        $this->cookies = $cookies;
    }

    /**
     * @param string $action
     * @param \watoki\collections\Map|null $args
     * @return array
     */
    public function doGet($action, Map $args = null) {
        $args = $args ? : new Map();

        $this->storeLastQuery($args, $action);

        $result = $this->doAction($this->dispatcher, $args, $action, self::TYPE);

        if ($result instanceof Responder) {
            return $result;
        }

        $crumbs = $this->updateBreadcrumb($args, $action);
        $breadcrumbs = $this->assembleBreadcrumbs($crumbs);

        return [
            'breadcrumbs' => $breadcrumbs,
            'entity' => $this->assembleResult($result)
        ];
    }

    private function assembleResult($result) {
        if (is_array($result)) {
            return array_map(function ($entity) {
                return $this->assembleEntity($entity);
            }, $result);
        } else if (is_object($result)) {
            return $this->assembleEntity($result);
        } else {
            throw new \InvalidArgumentException("Action had no displayable result: " . var_export($result, true));
        }
    }

    private function assembleEntity($entity) {
        $representer = $this->registry->getEntityRepresenter($entity);
        return [
            'name' => $representer->toString($entity),
            'properties' => $this->assembleProperties($entity),
            'queries' => $this->assembleQueries($entity),
            'commands' => $this->assembleCommands($entity)
        ];
    }

    private function assembleProperties($entity) {
        $properties = [];

        $representer = $this->registry->getEntityRepresenter($entity);
        foreach ($representer->getProperties($entity) as $property) {
            if ($property->canGet()) {
                $properties[] = $this->assembleProperty($property->name, $property->get());
            }
        }

        return $properties ? [
            'property' => $properties
        ] : null;
    }

    private function assembleProperty($name, $value) {
        if (is_object($value)) {
            $representer = $this->registry->getEntityRepresenter($value);
            $value = $representer->render($value);
        }
        return [
            'name' => $name,
            'value' => $value
        ];
    }

    private function assembleQueries($entity) {
        $queries = $this->registry->getEntityRepresenter($entity)->getQueries();
        return $this->assembleActions($queries, $entity, self::TYPE);
    }

    private function assembleCommands($entity) {
        $commands = $this->registry->getEntityRepresenter($entity)->getCommands();
        return $this->assembleActions($commands, $entity, CommandResource::TYPE);
    }

    private function assembleActions($actions, $entity, $type) {
        if (!$actions) {
            return null;
        }

        $representer = $this->registry->getEntityRepresenter($entity);
        $id = $representer->getId($entity);

        return [
            'action' => array_map(function ($query) use ($type, $id) {
                $representer = $this->registry->getActionRepresenter($query);
                return [
                    'name' => $representer->toString($this->factory->getInstance($query)),
                    'link' => [
                        'href' => "$type?action=$query"
                            . ($type == self::TYPE ? '' : '&do=post')
                            . ($id ? '&args[id]=' . $id : '')
                    ]
                ];
            }, $actions),
        ];
    }

    private function storeLastQuery(Map $args, $action) {
        $this->cookies->create(new Cookie([
            'action' => $action,
            'arguments' => $args->toArray()
        ]), self::LAST_QUERY_COOKIE);
    }

    private function updateBreadcrumb(Map $args, $action) {
        $crumbs = [];
        if ($this->cookies->hasKey(self::BREADCRUMB_COOKIE)) {
            $crumbs = $this->cookies->read(self::BREADCRUMB_COOKIE)->payload;

            $newCrumbs = [];
            foreach ($crumbs as $crumb) {
                list($label, $crumbAction, $crumbArgs) = $crumb;
                if ($action == $crumbAction && $args->toArray() == $crumbArgs) {
                    break;
                }
                $newCrumbs[] = $crumb;
            }
            $crumbs = $newCrumbs;
        }

        $representer = $this->registry->getActionRepresenter($action);
        $caption = $representer->toString($action);

        $crumbs[] = [$caption, $action, $args->toArray()];

        $this->cookies->create(new Cookie($crumbs), self::BREADCRUMB_COOKIE);

        return $crumbs;
    }

    private function assembleBreadcrumbs($crumbs) {
        $last = array_pop($crumbs);

        return [
            'breadcrumb' => array_map(function ($crumb) {
                list($caption, $action, $args) = $crumb;
                $url = Url::fromString('query');
                $url->getParameters()->set('action', $action);
                $url->getParameters()->set('args', new Map($args));
                return [
                    'caption' => $caption,
                    'link' => ['href' => $url->toString()]
                ];
            }, $crumbs),
            'current' => $last[0]
        ];
    }
}