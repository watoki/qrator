<?php
namespace watoki\cqurator;

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

} 