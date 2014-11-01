<?php
namespace watoki\qrator;

interface EntityRepresenter extends Representer {

    /**
     * @param object $object
     * @return string
     */
    public function render($object);

    /**
     * @return \watoki\qrator\representer\ActionGenerator[]
     */
    public function getQueries();

    /**
     * @return \watoki\qrator\representer\ActionGenerator[]
     */
    public function getCommands();

    /**
     * @param string $property
     * @return \watoki\qrator\representer\ActionGenerator[]
     */
    public function getPropertyQueries($property);

    /**
     * @param string $property
     * @return \watoki\qrator\representer\ActionGenerator[]
     */
    public function getPropertyCommands($property);

} 