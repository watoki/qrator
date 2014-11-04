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
     * @return \watoki\qrator\representer\ActionGenerator|null
     */
    public function getReadAction();

    /**
     * @return \watoki\qrator\representer\ActionGenerator|null
     */
    public function getListAction();

    /**
     * @param object|null $object
     * @return \watoki\collections\Map|\watoki\qrator\representer\Property[]  indexed by property name
     */
    public function getCondensedProperties($object);

} 