<?php
namespace watoki\qrator;

interface Representer {

    /**
     * @param string $class
     * @return string
     */
    public function getName($class);

    /**
     * @param object $object
     * @return string
     */
    public function toString($object);

    /**
     * @param object|string $action Object or class reference
     * @return array|\watoki\qrator\representer\property\ObjectProperty[]
     */
    public function getProperties($action);

    /**
     * @param object $object
     * @return mixed
     */
    public function getId($object);

}