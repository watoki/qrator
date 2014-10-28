<?php
namespace spec\watoki\cqurator\fixtures;

use watoki\scrut\Fixture;
use watoki\cqurator\web\IndexResource;
use watoki\cqurator\representer\GenericRepresenter;
use watoki\cqurator\RepresenterRegistry;
use watoki\scrut\Specification;

class RegistryFixture extends Fixture {

    /** @var RepresenterRegistry */
    public $registry;

    /** @var GenericRepresenter[] */
    public $representers = array();

    public function setUp() {
        parent::setUp();
        $this->registry = new RepresenterRegistry($this->spec->factory);
    }

    public function givenIRegisteredARepresenterFor($class) {
        $this->representers[$class] = new GenericRepresenter($this->spec->factory);
        $this->registry->register($class, $this->representers[$class]);
    }

    public function givenIAddedTheQuery_ToTheRepresenterOf($query, $class) {
        $this->representers[$class]->addQuery($query);
    }

} 