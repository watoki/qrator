<?php
namespace watoki\cqurator;

interface EntityRepresenter extends Representer {

    /**
     * @param object $value
     * @return string
     */
    public function render($value);

    /**
     * @return \string[]
     */
    public function getQueries();

    /**
     * @return \string[]
     */
    public function getCommands();

} 