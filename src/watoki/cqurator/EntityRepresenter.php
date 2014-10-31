<?php
namespace watoki\cqurator;

interface EntityRepresenter extends Representer {

    /**
     * @param object $object
     * @return string
     */
    public function render($object);

    /**
     * @return \watoki\cqurator\representer\ActionGenerator[]
     */
    public function getQueries();

    /**
     * @return \watoki\cqurator\representer\ActionGenerator[]
     */
    public function getCommands();

    /**
     * @param string $property
     * @return \watoki\cqurator\representer\ActionGenerator[]
     */
    public function getPropertyQueries($property);

    /**
     * @param string $property
     * @return \watoki\cqurator\representer\ActionGenerator[]
     */
    public function getPropertyCommands($property);

} 