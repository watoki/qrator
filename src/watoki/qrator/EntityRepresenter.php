<?php
namespace watoki\qrator;

interface EntityRepresenter extends Representer {

    /**
     * @param object $object
     * @return string
     */
    public function render($object);

    /**
     * @return \watoki\qrator\representer\ActionGenerator[]
     */
    public function getActions();

    /**
     * @param string $property
     * @return \watoki\qrator\representer\ActionGenerator[]
     */
    public function getPropertyActions($property);

    /**
     * @return object|null
     */
    public function getReadAction();

    /**
     * @return object|null
     */
    public function getListAction();

} 