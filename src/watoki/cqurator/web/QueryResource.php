<?php
namespace watoki\cqurator\web;

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
     * @param Request $request <-
     * @param string $action
     * @return array
     */
    public function doGet(Request $request, $action) {
        $this->storeLastQuery($request, $action);

        $result = $this->doAction($this->dispatcher, $request, $action, self::TYPE);

        if ($result instanceof Responder) {
            return $result;
        }

        $crumbs = $this->storeBreadcrumb($request, $action);
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
        $representer = $this->registry->getRepresenter(get_class($entity));
        return [
            'name' => $representer->toString($entity),
            'properties' => $this->assembleProperties($entity),
            'queries' => $this->assembleQueries($entity),
            'commands' => $this->assembleCommands($entity)
        ];
    }

    private function assembleProperties($object) {
        $properties = [];

        $representer = $this->registry->getRepresenter(get_class($object));
        foreach ($representer->getProperties($object) as $property) {
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
            $representer = $this->registry->getRepresenter(get_class($value));
            $value = $representer->render($value);
        }
        return [
            'name' => $name,
            'value' => $value
        ];
    }

    private function assembleQueries($object) {
        $queries = $this->registry->getRepresenter(get_class($object))->getQueries();
        return $this->assembleActions($queries, $object, self::TYPE);
    }

    private function assembleCommands($object) {
        $commands = $this->registry->getRepresenter(get_class($object))->getCommands();
        return $this->assembleActions($commands, $object, CommandResource::TYPE);
    }

    private function assembleActions($actions, $object, $type) {
        if (!$actions) {
            return null;
        }

        $representer = $this->registry->getRepresenter(get_class($object));
        $id = $representer->getId($object);

        return [
            'action' => array_map(function ($query) use ($type, $id) {
                $representer = $this->registry->getRepresenter($query);
                return [
                    'name' => $representer->toString($this->factory->getInstance($query)),
                    'link' => [
                        'href' => "$type?action=$query"
                            . ($type == self::TYPE ? '' : '&do=post')
                            . ($id ? '&id=' . $id : '')
                    ]
                ];
            }, $actions),
        ];
    }

    private function storeLastQuery(Request $request, $action) {
        $this->cookies->create(new Cookie([
            'action' => $action,
            'arguments' => $request->getArguments()->toArray()
        ]), self::LAST_QUERY_COOKIE);
    }

    private function storeBreadcrumb(Request $request, $action) {
        $crumbs = [];
        if ($this->cookies->hasKey(self::BREADCRUMB_COOKIE)) {
            $crumbs = $this->cookies->read(self::BREADCRUMB_COOKIE)->payload;

            $newCrumbs = [];
            foreach ($crumbs as $crumb) {
                $url = Url::fromString($crumb[1]);
                if ($this->matchesTarget($request, $url)) {
                    break;
                }
                $newCrumbs[] = $crumb;
            }
            $crumbs = $newCrumbs;
        }

        $breadcrumbUrl = Url::fromString('query');
        $breadcrumbUrl->getParameters()->set('action', $action);
        $breadcrumbUrl->getParameters()->merge($request->getArguments());

        $representer = $this->registry->getRepresenter($action);
        $object = $this->createAction($action);
        $this->prepareAction($request, $object);
        $caption = $representer->toString($object);

        $crumbs[] = [$caption, $breadcrumbUrl->toString()];

        $this->cookies->create(new Cookie($crumbs), self::BREADCRUMB_COOKIE);

        return $crumbs;
    }

    private function matchesTarget(Request $request, Url $url) {
        foreach ($request->getArguments() as $key => $value) {
            if (!$url->getParameters()->has($key) || $url->getParameters()->get($key) != $value) {
                return false;
            }
        }
        return true;
    }

    private function assembleBreadcrumbs($crumbs) {
        $last = array_pop($crumbs);

        return [
            'breadcrumb' => array_map(function ($crumb) {
                list($caption, $target) = $crumb;
                return [
                    'caption' => $caption,
                    'link' => ['href' => $target]
                ];
            }, $crumbs),
            'current' => $last[0]
        ];
    }
}