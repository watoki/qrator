<?php
namespace watoki\qrator;

use watoki\collections\Map;
use watoki\qrator\representer\Property;

interface ActionRepresenter extends Representer {

    /**
     * @param object $object of the action to be executed
     * @return mixed
     */
    public function execute($object);

    /**
     * @param object $object
     * @return array|\watoki\qrator\form\Field[]
     */
    public function getFields($object);

    /**
     * @param \watoki\qrator\representer\Property $property
     * @return \watoki\qrator\form\Field
     */
    public function getField(Property $property);

    /**
     * @param \watoki\collections\Map|null $args
     * @internal param $action
     * @return object
     */
    public function create(Map $args = null);

    /**
     * @param object $object
     * @return bool
     */
    public function hasMissingProperties($object);

    /**
     * @param object $result
     * @return null|object
     */
    public function getFollowUpAction($result);

    /**
     * @param object $object
     * @return void
     */
    public function preFill($object);

} 