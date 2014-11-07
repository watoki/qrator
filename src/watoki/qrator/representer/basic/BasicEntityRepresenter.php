<?php
namespace watoki\qrator\representer\basic;

use watoki\collections\Map;
use watoki\qrator\EntityRepresenter;

abstract class BasicEntityRepresenter extends BasicRepresenter implements EntityRepresenter {

    /**
     * @param object $entity
     * @return string
     */
    public function render($entity) {
        return $this->toString($entity);
    }

    /**
     * @param object $entity
     * @return \watoki\qrator\representer\ActionLink[]
     */
    public function getActions($entity) {
        return [];
    }

    /**
     * @param object $entity
     * @param \watoki\qrator\representer\Property $property
     * @return \watoki\qrator\representer\ActionLink[]
     */
    public function getPropertyActions($entity, $property) {
        return [];
    }

    /**
     * @param object $entity
     * @return object|null
     */
    public function getReadAction($entity) {
        return null;
    }

    /**
     * @return object|null
     */
    public function getListAction() {
        return null;
    }

    /**
     * @param null|object $object
     * @return Map|\watoki\qrator\representer\Property[]
     */
    public function getCondensedProperties($object) {
        $properties = $this->getProperties($object);
        return $properties->select($properties->keys()->limit(5));
    }
}