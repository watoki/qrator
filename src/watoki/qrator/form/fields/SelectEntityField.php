<?php
namespace watoki\qrator\form\fields;

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

    protected function getOptions() {
        $representer = $this->registry->getEntityRepresenter($this->entityClass);
        $listAction = $representer->getListAction();

        $options = [];
        foreach ($this->registry->getActionRepresenter($listAction)->execute($listAction) as $entity) {
            $options[$representer->getId($entity)] = $representer->toString($entity);
        }
        return $options;
    }

} 
