<?php
namespace watoki\qrator\representer\basic;

use watoki\collections\Map;
use watoki\qrator\EntityRepresenter;
use watoki\qrator\representer\ActionGenerator;

abstract class BasicEntityRepresenter extends BasicRepresenter implements EntityRepresenter {

    const CONDENSED_LIMIT = 5;

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

    /**
     * @param null|object $object
     * @return void|Map|\watoki\qrator\representer\Property[]
     */
    public function getCondensedProperties($object) {
        $properties = $this->getProperties($object);
        $slice = new Map();
        foreach ($properties->keys() as $i => $name) {
            $slice->set($name, $properties->get($name));
            if ($i == self::CONDENSED_LIMIT - 1) {
                break;
            }
        }
        return $slice;
    }
}