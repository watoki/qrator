<?php
namespace watoki\cqurator\web;

use watoki\collections\Map;
use watoki\cqurator\RepresenterRegistry;
use watoki\curir\cookie\CookieStore;
use watoki\curir\protocol\Url;
use watoki\curir\Responder;
use watoki\curir\responder\Redirecter;
use watoki\deli\Request;
use watoki\factory\Factory;
use watoki\smokey\Dispatcher;

class CommandResource extends ActionResource {

    const TYPE = 'command';

    /** @var \watoki\smokey\Dispatcher */
    private $dispatcher;

    /** @var \watoki\curir\cookie\CookieStore */
    private $cookies;

    /**
     * @param Factory $factory <-
     * @param Dispatcher $dispatcher <-
     * @param RepresenterRegistry $registry <-
     * @param \watoki\curir\cookie\CookieStore $cookies <-
     */
    function __construct(Factory $factory, Dispatcher $dispatcher, RepresenterRegistry $registry, CookieStore $cookies) {
        parent::__construct($factory, $registry);
        $this->dispatcher = $dispatcher;
        $this->cookies = $cookies;
    }

    /**
     * @param Request $request <-
     * @param $command
     * @return \watoki\curir\Responder
     */
    public function doPost(Request $request, $command) {
        $returned = $this->doAction($this->dispatcher, $request, $command, self::TYPE);
        if ($returned instanceof Responder) {
            return $returned;
        }

        if ($this->cookies->hasKey(QueryResource::LAST_QUERY_COOKIE)) {
            $lastQuery = $this->cookies->read(QueryResource::LAST_QUERY_COOKIE)->payload;

            $url = Url::fromString('query');
            $url->getParameters()->set('action', $lastQuery['action']);
            $url->getParameters()->merge(new Map($lastQuery['arguments']));

            return new Redirecter($url);
        }

        return "Command executed: " . $command;
    }
}