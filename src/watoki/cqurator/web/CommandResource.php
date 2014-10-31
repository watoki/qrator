<?php
namespace watoki\cqurator\web;

use watoki\collections\Map;
use watoki\cqurator\ActionDispatcher;
use watoki\cqurator\RepresenterRegistry;
use watoki\curir\cookie\CookieStore;
use watoki\curir\protocol\Url;
use watoki\curir\responder\Redirecter;
use watoki\curir\Responder;
use watoki\factory\Factory;

class CommandResource extends ActionResource {

    const TYPE = 'command';

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
     * @return \watoki\curir\Responder
     */
    public function doPost($action, Map $args = null) {
        $args = $args ? : new Map();

        $representer = $this->registry->getActionRepresenter($action);

        $object = $representer->create($action, $args);
        if ($representer->hasMissingProperties($object)) {
            return $this->redirectToPrepare($action, $args, self::TYPE);
        }

        $this->fireAction($object);

        if ($this->cookies->hasKey(QueryResource::LAST_QUERY_COOKIE)) {
            $lastQuery = $this->cookies->read(QueryResource::LAST_QUERY_COOKIE)->payload;

            $url = Url::fromString('query');
            $url->getParameters()->set('action', $lastQuery['action']);
            $url->getParameters()->set('args', new Map($lastQuery['arguments']));

            return new Redirecter($url);
        }

        return "Command executed: " . $action;
    }
}