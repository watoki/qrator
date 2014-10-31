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
     * @param object $object
     * @return array|\watoki\cqurator\representer\Property[]
     */
    public function getProperties($object);

    /**
     * @param object $object
     * @return mixed
     */
    public function getId($object);

}