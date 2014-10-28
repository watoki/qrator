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
            'properties' => null,
            'queries' => $this->assembleQueries($result),
            'commands' => $this->assembleCommands($result)
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
                        'href' => "?action=$command"
                    ]
                ];
            }, $commands),
        ] : null;
    }
}