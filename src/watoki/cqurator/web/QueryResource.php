<?php
namespace watoki\cqurator\web;

use watoki\cqurator\RepresenterRegistry;
use watoki\curir\protocol\Url;
use watoki\curir\responder\Redirecter;
use watoki\deli\Request;
use watoki\factory\Factory;
use watoki\smokey\Dispatcher;

class QueryResource {
    const TYPE = 'query';

    /** @var \watoki\smokey\Dispatcher */
    private $dispatcher;

    /** @var RepresenterRegistry */
    private $registry;

    /** @var \watoki\factory\Factory */
    private $factory;

    /**
     * @param Dispatcher $dispatcher <-
     * @param RepresenterRegistry $registry <-
     * @param Factory $factory <-
     */
    function __construct(Dispatcher $dispatcher, RepresenterRegistry $registry, Factory $factory) {
        $this->dispatcher = $dispatcher;
        $this->registry = $registry;
        $this->factory = $factory;
    }


    /**
     * @param Request $request <-
     * @param string $query
     * @return array
     */
    public function doGet(Request $request, $query) {
        $result = null;

        try {
            $action = $this->createAction($request, $query);
        } catch (\UnderflowException $e) {
            $target = Url::fromString('prepare');
            $target->getParameters()->set('action', $query);
            $target->getParameters()->set('type', self::TYPE);
            $target->getParameters()->merge($request->getArguments());
            return new Redirecter($target);
        }

        $this->dispatcher->fire($action)
            ->onSuccess(function ($returned) use (&$result) {
                $result = $returned;
            })
            ->onException(function (\Exception $e) {
                throw $e;
            });

        return [
            'entity' => $this->assembleResult($result)
        ];
    }

    private function createAction(Request $request, $actionClass) {
        $action = $this->factory->getInstance($actionClass);
        $actionClass = get_class($action);

        $getParameter = function ($property) use ($request, $actionClass) {
            if (!$request->getArguments()->has($property)) {
                throw new \UnderflowException("Property [$property] for action [$actionClass] missing");
            }
            return $request->getArguments()->get($property);
        };

        foreach ($action as $property => $value) {
            $action->$property = $getParameter($property);
        }
        foreach (get_class_methods($actionClass) as $method) {
            if (substr($method, 0, 3) == 'set') {
                call_user_func(array($action, $method), $getParameter(lcfirst(substr($method, 3))));
            }
        }

        return $action;
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
        return [
            'name' => get_class($entity),
            'properties' => $this->assembleProperties($entity),
            'queries' => $this->assembleQueries($entity),
            'commands' => $this->assembleCommands($entity)
        ];
    }

    private function assembleProperties($object) {
        $properties = array();

        foreach ($object as $property => $value) {
            $properties[] = $this->assembleProperty($property, $value);
        }

        $reflection = new \ReflectionClass($object);
        foreach ($reflection->getMethods() as $method) {
            if ($method->isPublic() && substr($method->getName(), 0, 3) == 'get') {
                $properties[] = $this->assembleProperty(substr($method->getName(), 3), $method->invoke($object));
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
        $class = get_class($object);
        $queries = $this->registry->getRepresenter($class)->getQueries();
        return $this->assembleActions($queries, $object);
    }

    private function assembleCommands($object) {
        $class = get_class($object);
        $commands = $this->registry->getRepresenter($class)->getCommands();
        return $this->assembleActions($commands, $object, '&do=post');
    }

    private function assembleActions($actions, $object, $urlSuffix = '') {
        if (!$actions) {
            return null;
        }

        $representer = $this->registry->getRepresenter(get_class($object));
        $id = $representer->getId($object);

        return [
            'action' => array_map(function ($query) use ($urlSuffix, $id) {
                return [
                    'name' => $query,
                    'link' => [
                        'href' => "?action=$query" . $urlSuffix . ($id ? '&id=' . $id : '')
                    ]
                ];
            }, $actions),
        ];
    }
}