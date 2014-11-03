<?php
namespace watoki\qrator\form\fields;

use watoki\qrator\ActionRepresenter;
use watoki\qrator\EntityRepresenter;
use watoki\smokey\Dispatcher;

class SelectEntityField extends SelectField {

    /** @var object */
    private $listAction;

    /** @var \watoki\qrator\EntityRepresenter */
    private $entity;

    /** @var \watoki\qrator\ActionRepresenter */
    private $action;

    /**
     * @param string $name
     * @param object $listAction
     * @param EntityRepresenter $representer
     * @param ActionRepresenter $action
     */
    public function __construct($name, $listAction, EntityRepresenter $representer, ActionRepresenter $action) {
        parent::__construct($name);
        $this->listAction = $listAction;
        $this->entity = $representer;
        $this->action = $action;
    }

    protected function getOptions() {
        $options = [];
        foreach ($this->action->execute($this->listAction) as $entity) {
            $options[$this->entity->getId($entity)] = $this->entity->toString($entity);
        }
        return $options;
    }

} 
