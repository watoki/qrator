<?php
namespace watoki\cqurator\web;

use watoki\cqurator\RepresenterRegistry;
use watoki\curir\Container;
use watoki\factory\Factory;

class IndexResource extends Container {

    /** @var RepresenterRegistry */
    private $registry;

    function __construct(Factory $factory, RepresenterRegistry $registry) {
        parent::__construct($factory);
        $this->registry = $registry;
    }

    public function doGet() {
        return [
            'query' => array_map(function ($query) {
                return [
                    'name' => $query,
                    'link' => [
                        'href' => 'query?action=' . $query
                    ]
                ];
            }, $this->registry->getRepresenter(null)->getQueries())
        ];
    }
}