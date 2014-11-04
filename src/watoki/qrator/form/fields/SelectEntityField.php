<?php
namespace watoki\qrator\form\fields;

use watoki\collections\Map;
use watoki\qrator\RepresenterRegistry;

class SelectEntityField extends SelectField {

    /** @var \watoki\qrator\RepresenterRegistry */
    private $registry;

    /** @var string */
    private $entityClass;

    /**
     * @param string $name
     * @param string $entityClass
     * @param \watoki\qrator\RepresenterRegistry $registry
     */
    public function __construct($name, $entityClass, RepresenterRegistry $registry) {
        parent::__construct($name);
        $this->registry = $registry;
        $this->entityClass = $entityClass;
    }

    /**
     * @return string
     */
    public function getEntityClass() {
        return $this->entityClass;
    }

    protected function getOptions() {
        $representer = $this->registry->getEntityRepresenter($this->entityClass);
        $listActionGenerator = $representer->getListAction();
        $actionRepresenter = $this->registry->getActionRepresenter($listActionGenerator->getClass());
        $action = $actionRepresenter->create(new Map($listActionGenerator->getArguments(null)));

        $options = [];
        foreach ($actionRepresenter->execute($action) as $entity) {
            $options[$representer->getId($entity)] = $representer->toString($entity);
        }
        return $options;
    }

} 
