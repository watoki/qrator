<?php
namespace watoki\qrator\form\fields;

use watoki\qrator\RepresenterRegistry;

class SelectEntityField extends SelectField {

    /** @var \watoki\qrator\RepresenterRegistry */
    private $registry;

    /** @var string */
    private $entityClass;

    /** @var null|callable */
    private $inflater;

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

    public function setInflater($callback) {
        $this->inflater = $callback;
    }

    public function inflate($value) {
        if ($this->inflater) {
            return call_user_func($this->inflater, $value);
        }
        return parent::inflate($value);
    }

    /**
     * @return string
     */
    public function getEntityClass() {
        return $this->entityClass;
    }

    protected function getEntityList() {
        $representer = $this->registry->getEntityRepresenter($this->entityClass);
        $listActionLink = $representer->getListAction();

        if (!$listActionLink) {
            throw new \Exception("Cannot select [{$this->entityClass}]: " .
                "The EntityRepresenter did not provide a ListAction.");
        }

        $actionRepresenter = $this->registry->getActionRepresenter($listActionLink->getClass());
        return $actionRepresenter->execute($actionRepresenter->create($listActionLink->getArguments()));
    }

    protected function getOptions() {
        $representer = $this->registry->getEntityRepresenter($this->entityClass);

        $options = [];
        foreach ($this->getEntityList() as $entity) {
            $options[(string)$representer->getProperties($entity)[$representer->keyProperty()]->get($entity)] = $representer->toString($entity);
        }
        return $options;
    }

} 
