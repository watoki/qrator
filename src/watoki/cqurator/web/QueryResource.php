<?php
namespace watoki\cqurator\web;

use watoki\collections\Map;
use watoki\cqurator\ActionDispatcher;
use watoki\cqurator\representer\ActionGenerator;
use watoki\cqurator\representer\PropertyActionGenerator;
use watoki\cqurator\RepresenterRegistry;
use watoki\curir\cookie\Cookie;
use watoki\curir\cookie\CookieStore;
use watoki\curir\protocol\Url;
use watoki\curir\Responder;
use watoki\factory\Factory;
use watoki\tempan\model\ListModel;

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
     * @param bool $prepared
     * @return array
     */
    public function doGet($action, Map $args = null, $prepared = false) {
        $args = $args ? : new Map();

        $result = $this->doAction($action, $args, $prepared, self::TYPE);
        if ($result instanceof Responder) {
            return $result;
        }

        $this->storeLastQuery($action, $args);

        $crumbs = $this->updateBreadcrumb($action, $args);
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
            'name' => $representer->getName(get_class($entity)),
            'properties' => $this->assembleProperties($entity),
            'queries' => new ListModel($this->assembleQueries($entity)),
            'commands' => new ListModel($this->assembleCommands($entity))
        ];
    }

    private function assembleProperties($entity) {
        $properties = [];

        $representer = $this->registry->getEntityRepresenter($entity);
        foreach ($representer->getProperties($entity) as $property) {
            if ($property->canGet()) {
                $properties[] = $this->assembleProperty($entity, $property->name(), $property->get());
            }
        }

        return $properties ? [
            'property' => $properties
        ] : null;
    }

    private function assembleProperty($entity, $name, $value) {
        return [
            'name' => $name,
            'value' => $this->assembleValue($entity, $name, $value)
        ];
    }

    private function assembleValue($entity, $name, $value) {
        if (is_object($value)) {
            $entityRepresenter = $this->registry->getEntityRepresenter($entity);
            $representer = $this->registry->getEntityRepresenter($value);

            return [
                'caption' => $representer->render($value),
                'queries' => array_merge(
                    $this->assembleQueries($value),
                    $this->assemblePropertyActions($entityRepresenter->getPropertyQueries($name), $value, $entity, self::TYPE)
                ),
                'commands' => array_merge(
                    $this->assembleCommands($value),
                    $this->assemblePropertyActions($entityRepresenter->getPropertyCommands($name), $value, $entity, CommandResource::TYPE)
                ),
            ];
        } else if (is_array($value)) {
            array_walk($value, function (&$item) use ($entity, $name) {
                $item = $this->assembleValue($entity, $name, $item);
            });
            return $value;
        }
        return [
            'caption' => $value,
            'queries' => null,
            'commands' => null
        ];
    }

    private function assemblePropertyActions($actions, $object, $entity, $type) {
        $representer = $this->registry->getEntityRepresenter($entity);
        $id = $representer->getId($entity);

        $propertyRepresenter = $this->registry->getEntityRepresenter($object);
        $propertyId = $propertyRepresenter->getId($object);;

        return array_map(function (PropertyActionGenerator $action) use ($type, $id, $propertyId) {
            return $this->assembleAction($action->getClass(), $type, $action->getArguments($id, $propertyId));
        }, $actions);
    }

    private function assembleQueries($entity) {
        $representer = $this->registry->getEntityRepresenter($entity);
        return $this->assembleActions($representer->getQueries(), $entity, self::TYPE);
    }

    private function assembleCommands($entity) {
        $representer = $this->registry->getEntityRepresenter($entity);
        return $this->assembleActions($representer->getCommands(), $entity, CommandResource::TYPE);
    }

    private function assembleActions($actions, $entity, $type) {
        $representer = $this->registry->getEntityRepresenter($entity);
        $id = $representer->getId($entity);

        return array_map(function (ActionGenerator $action) use ($type, $id) {
            return $this->assembleAction($action->getClass(), $type, $action->getArguments($id));
        }, $actions);
    }

    private function assembleAction($action, $type, $arguments) {
        $target = Url::fromString($type);
        $target->getParameters()->set('action', $action);
        if ($type == CommandResource::TYPE) {
            $target->getParameters()->set('do', 'post');
        }
        $target->getParameters()->set('args', new Map($arguments));


        $representer = $this->registry->getActionRepresenter($action);
        return [
            'name' => $representer->getName($action),
            'link' => [
                'href' => $target->toString()
            ]
        ];
    }

    private function storeLastQuery($action, Map $args) {
        $this->cookies->create(new Cookie([
            'action' => $action,
            'arguments' => $args->toArray()
        ]), self::LAST_QUERY_COOKIE);
    }

    private function updateBreadcrumb($action, Map $args) {

        $crumbs = [];
        if ($this->cookies->hasKey(self::BREADCRUMB_COOKIE)) {
            $crumbs = $this->cookies->read(self::BREADCRUMB_COOKIE)->payload;

            $newCrumbs = [];
            foreach ($crumbs as $crumb) {
                /** @noinspection PhpUnusedLocalVariableInspection */
                list($label, $crumbAction, $crumbArgs) = $crumb;
                if ($action == $crumbAction && $args->toArray() == $crumbArgs) {
                    break;
                }
                $newCrumbs[] = $crumb;
            }
            $crumbs = $newCrumbs;
        }

        $representer = $this->registry->getActionRepresenter($action);
        $object = $representer->create($action, $args);
        $caption = $representer->toString($object);

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