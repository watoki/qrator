<?php
namespace watoki\qrator\representer\basic;

use watoki\qrator\EntityRepresenter;
use watoki\qrator\representer\ActionGenerator;

abstract class BasicEntityRepresenter extends BasicRepresenter implements EntityRepresenter {


    /**
     * @param object $object
     * @return string
     */
    public function render($object) {
        return $this->toString($object);
    }

    protected function wrapInActionGenerators($classes) {
        $generators = [];
        foreach ($classes as $class => $args) {
            if (is_numeric($class)) {
                $class = $args;
                $args = null;
            }
            $generators[] = new ActionGenerator($class, $args);
        }
        return $generators;
    }

    /**
     * @return ActionGenerator[]
     */
    public function getActions() {
        return [];
    }

    /**
     * @param string $property
     * @return ActionGenerator[]
     */
    public function getPropertyActions($property) {
        return [];
    }

    /**
     * @return object|null
     */
    public function getReadAction() {
        return null;
    }

    /**
     * @return object|null
     */
    public function getListAction() {
        return null;
    }
}