<?php
namespace watoki\qrator;

interface Representer {

    /**
     * @return string
     */
    public function getClass();

    /**
     * @return string
     */
    public function getName();

    /**
     * @param object $object
     * @return string
     */
    public function toString($object);

    /**
     * @param object|null $object
     * @return \watoki\collections\Map|\watoki\reflect\Property[]  indexed by property name
     */
    public function getProperties($object = null);

}