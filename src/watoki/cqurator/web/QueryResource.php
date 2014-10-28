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
            'properties' => $this->assembleProperties($result),
            'queries' => $this->assembleQueries($result),
            'commands' => $this->assembleCommands($result)
        ];
    }

    private function assembleProperties($object) {
        $properties = array();

        foreach ($object as $property => $value) {
            $properties[] = [
                'name' => $property,
                'value' => print_r($value, true)
            ];
        }

        $reflection = new \ReflectionClass($object);
        foreach ($reflection->getMethods() as $method) {
            if ($method->isPublic() && substr($method->getName(), 0, 3) == 'get') {
                $properties[] = [
                    'name' => substr($method->getName(), 3),
                    'value' => $method->invoke($object)
                ];
            }
        }

        return $properties ? [
            'property' => $properties
        ] : null;
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