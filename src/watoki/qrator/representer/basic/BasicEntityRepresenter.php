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
     * @param string $propertyName
     * @param mixed $value
     * @return \watoki\qrator\representer\ActionLink[]
     */
    public function getPropertyActions($entity, $propertyName, $value) {
        return [];
    }

    /**
     * @param mixed $id
     * @return null|\watoki\qrator\representer\ActionLink
     */
    public function getReadAction($id) {
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
     * @return Map|\watoki\reflect\Property[]
     */
    public function getCondensedProperties($object) {
        $properties = $this->getProperties($object);
        return $properties->select($properties->keys()->limit(5));
    }
}