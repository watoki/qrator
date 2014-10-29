<?php
namespace watoki\cqurator;

use watoki\cqurator\contracts\Representer;
use watoki\cqurator\representer\GenericRepresenter;
use watoki\factory\Factory;

class RepresenterRegistry {

    /** @var array|Representer[] */
    private $representers = [];

    /** @var Factory */
    private $factory;

    /**
     * @param Factory $factory <-
     */
    public function __construct(Factory $factory) {
        $this->factory = $factory;
    }

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
            return new GenericRepresenter($this->factory);
        }
    }
}