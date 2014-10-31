<?php
namespace watoki\cqurator;

interface EntityRepresenter extends Representer {

    /**
     * @param object $object
     * @return string
     */
    public function render($object);

    /**
     * @return \string[]
     */
    public function getQueries();

    /**
     * @return \string[]
     */
    public function getCommands();

} 