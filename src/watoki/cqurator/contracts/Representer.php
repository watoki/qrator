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
}