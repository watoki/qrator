<?php
namespace watoki\qrator\form\fields;

use watoki\qrator\RepresenterRegistry;

class SelectEntityField extends SelectField {

    /** @var object */
    private $listAction;

    /** @var \watoki\qrator\RepresenterRegistry */
    private $registry;

    /**
     * @param string $name
     * @param object $listAction
     * @param \watoki\qrator\RepresenterRegistry $registry
     */
    public function __construct($name, $listAction, RepresenterRegistry $registry) {
        parent::__construct($name);
        $this->listAction = $listAction;
        $this->registry = $registry;
    }

    protected function getOptions() {
        $action = $this->registry->getActionRepresenter($this->listAction);

        $options = [];
        foreach ($action->execute($this->listAction) as $entity) {
            $representer = $this->registry->getEntityRepresenter($entity);
            $options[$representer->getId($entity)] = $representer->toString($entity);
        }
        return $options;
    }

} 
