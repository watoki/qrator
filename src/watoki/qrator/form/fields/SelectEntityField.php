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
        $listActionLink = $representer->getListAction();

        if (!$listActionLink) {
            throw new \Exception("Cannot select [{$this->entityClass}]: list action not set.");
        }

        $actionRepresenter = $this->registry->getActionRepresenter($listActionLink->getClass());

        $options = [];
        foreach ($actionRepresenter->execute($actionRepresenter->create($listActionLink->getArguments())) as $entity) {
            $options[$representer->getProperties($entity)['id']->get($entity)] = $representer->toString($entity);
        }
        return $options;
    }

} 
