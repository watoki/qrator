<?php
namespace watoki\cqurator;

interface Representer {

    /**
     * @return \string[]
     */
    public function getQueries();

    /**
     * @return \string[]
     */
    public function getCommands();

    /**
     * @param object $value
     * @return string
     */
    public function render($value);

    /**
     * @param object $object
     * @return mixed
     */
    public function getId($object);

    /**
     * @param object $object
     * @return array|\watoki\cqurator\form\Field[]
     */
    public function getFields($object);

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
     * @param string $name
     * @return \watoki\cqurator\form\Field
     */
    public function getField($name);
}