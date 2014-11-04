<?php
namespace watoki\qrator;

use watoki\collections\Map;

interface ActionRepresenter extends Representer {

    /**
     * @param object $object of the action to be executed
     * @return mixed
     */
    public function execute($object);

    /**
     * @param object|null $object
     * @return array|\watoki\qrator\form\Field[] Without ID
     */
    public function getFields($object);

    /**
     * @param string $name
     * @return \watoki\qrator\form\Field
     */
    public function getField($name);

    /**
     * @param \watoki\collections\Map $args
     * @internal param $action
     * @return object
     */
    public function create(Map $args);

    /**
     * @param object $object
     * @return bool
     */
    public function hasMissingProperties($object);

    /**
     * @return null|\watoki\qrator\representer\ActionGenerator
     */
    public function getFollowUpAction();

    /**
     * @param object $object
     * @return void
     */
    public function preFill($object);

} 