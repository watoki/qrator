<?php
namespace watoki\cqurator\web;

use watoki\cqurator\RepresenterRegistry;
use watoki\smokey\Dispatcher;

class QueryResource {

    /** @var \watoki\smokey\Dispatcher */
    private $dispatcher;

    /** @var RepresenterRegistry */
    private $registry;

    function __construct(Dispatcher $dispatcher, RepresenterRegistry $registry) {
        $this->dispatcher = $dispatcher;
        $this->registry = $registry;
    }


    public function doGet($query) {
        $result = null;

        $this->dispatcher->fire(new $query)
            ->onSuccess(function ($returned) use (&$result) {
                $result = $returned;
            });

        return [
            'entity' => $this->assembleResult($result)
        ];
    }

    private function assembleResult($result) {
        if (is_array($result)) {
            return array_map(function ($entity) {
                return $this->assembleEntity($entity);
            }, $result);
        } else {
            return $this->assembleEntity($result);
        }
    }

    private function assembleEntity($entity) {
        return [
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
        return $queries ? [
            'action' => array_map(function ($query) use ($class) {
                return [
                    'name' => $query,
                    'link' => [
                        'href' => "?action=$query"
                    ]
                ];
            }, $queries),
        ] : null;
    }

    private function assembleCommands($object) {
        $class = get_class($object);
        $commands = $this->registry->getRepresenter($class)->getCommands();
        return $commands ? [
            'action' => array_map(function ($command) use ($class) {
                return [
                    'name' => $command,
                    'link' => [
                        'href' => "?action=$command&do=post"
                    ]
                ];
            }, $commands),
        ] : null;
    }
}