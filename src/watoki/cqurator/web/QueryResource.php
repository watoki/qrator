<?php
namespace watoki\cqurator\web;

use watoki\smokey\Dispatcher;

class QueryResource {

    /** @var \watoki\smokey\Dispatcher */
    private $dispatcher;

    function __construct(Dispatcher $dispatcher) {
        $this->dispatcher = $dispatcher;
    }


    public function doGet($query) {
        $this->dispatcher->fire(new $query);
    }
}