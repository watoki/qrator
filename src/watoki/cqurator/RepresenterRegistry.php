<?php
namespace watoki\cqurator;

use watoki\cqurator\Representer;
use watoki\cqurator\representer\GenericRepresenter;

class RepresenterRegistry {

    /** @var array|Representer[] */
    private $representers = [];

    /**
     * @param string|null $class
     * @param Representer $representer
     */
    public function register($class, Representer $representer) {
        $this->representers[$class] = $representer;
    }

    /**
     * @param string|null $class
     * @return Representer
     */
    public function getRepresenter($class) {
        if (isset($this->representers[$class])) {
            return $this->representers[$class];
        } else {
            return new GenericRepresenter();
        }
    }
}