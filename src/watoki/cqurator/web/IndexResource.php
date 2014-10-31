<?php
namespace watoki\cqurator\web;

use watoki\cqurator\RepresenterRegistry;
use watoki\curir\Container;
use watoki\curir\rendering\adapter\TempanRenderer;
use watoki\factory\Factory;

class IndexResource extends Container {

    /** @var RepresenterRegistry */
    private $registry;

    /**
     * @param Factory $factory <-
     * @param RepresenterRegistry $registry <-
     */
    function __construct(Factory $factory, RepresenterRegistry $registry) {
        parent::__construct($factory);
        $this->registry = $registry;
    }

    protected function createDefaultRenderer() {
        return new TempanRenderer();
    }

    public function doGet() {
        return [
            'query' => array_map(function ($query) {
                $representer = $this->registry->getActionRepresenter($query);
                return [
                    'name' => $representer->getName($query),
                    'link' => [
                        'href' => 'query?action=' . $query
                    ]
                ];
            }, $this->registry->getEntityRepresenter(null)->getQueries())
        ];
    }
}