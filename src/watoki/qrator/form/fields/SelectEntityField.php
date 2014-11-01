<?php
namespace watoki\qrator\form\fields;

use watoki\qrator\EntityRepresenter;
use watoki\smokey\Dispatcher;

class SelectEntityField extends SelectField {

    /** @var \watoki\smokey\Dispatcher */
    private $dispatcher;

    /** @var object */
    private $listAction;

    /** @var \watoki\qrator\EntityRepresenter */
    private $representer;

    /**
     * @param string $name
     * @param object $listAction
     * @param EntityRepresenter $representer
     * @param Dispatcher $dispatcher
     */
    public function __construct($name, $listAction, EntityRepresenter $representer, Dispatcher $dispatcher) {
        parent::__construct($name);
        $this->dispatcher = $dispatcher;
        $this->listAction = $listAction;
        $this->representer = $representer;
    }

    protected function getOptions() {
        $options = [];
        $this->dispatcher->fire($this->listAction)->onSuccess(function ($list) use (&$options) {
            foreach ($list as $entity) {
                $options[$this->representer->getId($entity)] = $this->representer->toString($entity);
            }
        });
        return $options;
    }

} 
