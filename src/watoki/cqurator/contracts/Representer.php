<?php
namespace watoki\cqurator\contracts;

interface Representer {

    /**
     * @return Query[]
     */
    public function getQueries();

    /**
     * @return Command[]
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
     * @return array|mixed[] Indexed by property names
     */
    public function getPropertyValues($object);
}