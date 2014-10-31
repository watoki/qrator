<?php
namespace watoki\cqurator;

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
     * @return array|\watoki\cqurator\representer\property\ObjectProperty[]
     */
    public function getProperties($action);

    /**
     * @param object $object
     * @return mixed
     */
    public function getId($object);

}