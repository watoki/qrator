<?php
namespace watoki\qrator\representer\basic;

use watoki\qrator\EntityRepresenter;

abstract class BasicEntityRepresenter extends BasicRepresenter implements EntityRepresenter {


    /**
     * @param object $object
     * @return string
     */
    public function render($object) {
        return $this->toString($object);
    }

    /**
     * @return \watoki\qrator\representer\ActionGenerator[]
     */
    public function getActions() {
        return [];
    }

    /**
     * @param string $property
     * @return \watoki\qrator\representer\ActionGenerator[]
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