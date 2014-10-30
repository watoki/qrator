<?php
namespace watoki\cqurator\web;

use watoki\cqurator\RepresenterRegistry;
use watoki\deli\Request;
use watoki\factory\Factory;
use watoki\smokey\Dispatcher;

class CommandResource extends ActionResource {

    const TYPE = 'command';

    /** @var \watoki\smokey\Dispatcher */
    private $dispatcher;

    /**
     * @param Factory $factory <-
     * @param Dispatcher $dispatcher <-
     * @param RepresenterRegistry $registry <-
     */
    function __construct(Factory $factory, Dispatcher $dispatcher, RepresenterRegistry $registry) {
        parent::__construct($factory, $registry);
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param Request $request <-
     * @param $command
     * @return \watoki\curir\Responder
     */
    public function doPost(Request $request, $command) {
        return $this->doAction($this->dispatcher, $request, $command, self::TYPE);
    }
}