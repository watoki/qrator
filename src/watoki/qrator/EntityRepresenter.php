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
     * @param mixed $id
     * @return null|\watoki\qrator\representer\ActionLink
     */
    public function getReadAction($id);

    /**
     * @return \watoki\qrator\representer\ActionLink|null
     */
    public function getListAction();

    /**
     * @param object|null $object
     * @return \watoki\collections\Map|\watoki\reflect\Property[]  indexed by property name
     */
    public function getCondensedProperties($object);

    /**
     * @return string Name of key property
     */
    public function keyProperty();

} 