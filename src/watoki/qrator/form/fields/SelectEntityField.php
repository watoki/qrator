<?php
namespace watoki\qrator\form\fields;

use watoki\qrator\EntityRepresenter;
use watoki\smokey\Dispatcher;

class SelectEntityField extends SelectField {

    /** @var \watoki\smokey\Dispatcher */
    private $dispatcher;

    /** @var object */
    private $listQuery;

    /** @var \watoki\qrator\EntityRepresenter */
    private $representer;

    /**
     * @param string $name
     * @param object $listQuery
     * @param EntityRepresenter $representer
     * @param Dispatcher $dispatcher
     */
    public function __construct($name, $listQuery, EntityRepresenter $representer, Dispatcher $dispatcher) {
        parent::__construct($name);
        $this->dispatcher = $dispatcher;
        $this->listQuery = $listQuery;
        $this->representer = $representer;
    }

    protected function getOptions() {
        $options = [];
        $this->dispatcher->fire($this->listQuery)->onSuccess(function ($list) use (&$options) {
            foreach ($list as $entity) {
                $options[$this->representer->getId($entity)] = $this->representer->toString($entity);
            }
        });
        return $options;
    }

} 