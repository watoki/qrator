<?php
namespace watoki\qrator;

interface EntityRepresenter extends Representer {

    /**
     * @param object $entity
     * @return string
     */
    public function render($entity);

    /**
     * @param object $entity
     * @return \watoki\qrator\representer\ActionLink[]
     */
    public function getActions($entity);

    /**
     * @param object $entity
     * @param \watoki\qrator\representer\Property $property
     * @return \watoki\qrator\representer\ActionLink[]
     */
    public function getPropertyActions($entity, $property);

    /**
     * @param object $entity
     * @return object|null
     */
    public function getReadAction($entity);

    /**
     * @return object|null
     */
    public function getListAction();

    /**
     * @param object|null $object
     * @return \watoki\collections\Map|\watoki\qrator\representer\Property[]  indexed by property name
     */
    public function getCondensedProperties($object);

} 