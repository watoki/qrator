<?php
namespace watoki\qrator;

use watoki\collections\Map;

interface ActionRepresenter extends Representer {

    /**
     * @param object|string $action Object or class reference
     * @return array|\watoki\qrator\form\Field[] Without ID
     */
    public function getFields($action);

    /**
     * @param string $name
     * @return \watoki\qrator\form\Field
     */
    public function getField($name);

    /**
     * @param string $class
     * @param \watoki\collections\Map $args
     * @internal param $action
     * @return object
     */
    public function create($class, Map $args);

    /**
     * @param object $object
     * @return bool
     */
    public function hasMissingProperties($object);

} 