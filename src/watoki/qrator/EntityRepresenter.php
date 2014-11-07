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
     * @param string $propertyName
     * @param mixed $value
     * @return \watoki\qrator\representer\ActionLink[]
     */
    public function getPropertyActions($entity, $propertyName, $value);

    /**
     * @param object $entity
     * @return \watoki\qrator\representer\ActionLink|null
     */
    public function getReadAction($entity);

    /**
     * @return \watoki\qrator\representer\ActionLink|null
     */
    public function getListAction();

    /**
     * @param object|null $object
     * @return \watoki\collections\Map|\watoki\qrator\representer\Property[]  indexed by property name
     */
    public function getCondensedProperties($object);

} 