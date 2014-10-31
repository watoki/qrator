<?php
namespace watoki\cqurator;

use watoki\collections\Map;

interface ActionRepresenter extends Representer {

    /**
     * @param object $object
     * @return array|\watoki\cqurator\form\Field[] Without ID
     */
    public function getFields($object);

    /**
     * @param string $name
     * @return \watoki\cqurator\form\Field
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