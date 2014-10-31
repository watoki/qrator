<?php
namespace watoki\cqurator\form\fields;

use watoki\cqurator\EntityRepresenter;
use watoki\cqurator\RepresenterRegistry;
use watoki\smokey\Dispatcher;

class SelectEntityField extends SelectField {

    private $entityClass;

    /** @var \watoki\smokey\Dispatcher */
    private $dispatcher;

    /** @var \watoki\cqurator\RepresenterRegistry */
    private $registry;

    public function __construct($name, $entityClass, RepresenterRegistry $registry, Dispatcher $dispatcher) {
        parent::__construct($name);
        $this->dispatcher = $dispatcher;
        $this->registry = $registry;
        $this->entityClass = $entityClass;
    }

    protected function getOptions() {
        $representer = $this->registry->getEntityRepresenter($this->entityClass);
        $query = $representer->getListQuery();

        if (!$query) {
            throw new \Exception("The Representer of [{$this->entityClass}] must provide a listing query.");
        }

        $options = [];
        $this->dispatcher->fire($query)->onSuccess(function ($list) use (&$options, $representer) {
            foreach ($list as $entity) {
                $options[$representer->getId($entity)] = $representer->toString($entity);
            }
        });
        return $options;
    }

} 
