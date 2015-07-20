<?php
namespace watoki\qrator;

use watoki\collections\Map;
use watoki\reflect\Property;

interface ActionRepresenter extends Representer {

    /**
     * @return string HTML representation of the action
     */
    public function render();

    /**
     * @param object $object of the action to be executed
     * @return mixed
     */
    public function execute($object);

    /**
     * @param object|null $object
     * @return array|\watoki\qrator\form\Field[] Indexed by name
     */
    public function getFields($object = null);

    /**
     * @param \watoki\collections\Map|null $args
     * @return object
     */
    public function create(Map $args = null);

    /**
     * @param object $result
     * @return null|\watoki\qrator\representer\ActionLink
     */
    public function getFollowUpAction($result);

    /**
     * @param array|\watoki\qrator\form\Field[] $fields
     * @return void
     */
    public function preFill($fields);

    /**
     * @return string|null
     */
    public function requiresConfirmation();

    /**
     * @return \watoki\curir\protocol\Url of the Resource that represents the action
     */
    public function getResourceUrl();

} 