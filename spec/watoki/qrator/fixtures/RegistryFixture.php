<?php
namespace spec\watoki\qrator\fixtures;

use watoki\qrator\representer\ActionGenerator;
use watoki\qrator\representer\GenericActionRepresenter;
use watoki\qrator\representer\GenericEntityRepresenter;
use watoki\qrator\representer\PropertyActionGenerator;
use watoki\qrator\RepresenterRegistry;
use watoki\scrut\Fixture;

/**
 * @property ClassFixture class <-
 */
class RegistryFixture extends Fixture {

    /** @var RepresenterRegistry */
    public $registry;

    /** @var GenericActionRepresenter[]|GenericEntityRepresenter[] */
    public $representers = array();

    public function setUp() {
        parent::setUp();
        $this->registry = new RepresenterRegistry($this->spec->factory);
    }

    public function givenIRegisteredAnActionRepresenterFor($class) {
        if (array_key_exists($class, $this->representers)) {
            return;
        }
        $this->representers[$class] = new GenericActionRepresenter($class, $this->spec->factory);
        $this->registry->register($this->representers[$class]);
    }

    public function givenIRegisteredAnEntityRepresenterFor($class) {
        if (array_key_exists($class, $this->representers)) {
            return;
        }
        $this->representers[$class] = new GenericEntityRepresenter($class);
        $this->registry->register($this->representers[$class]);
    }

    public function givenIAddedTheAction_ToTheRepresenterOf($action, $class) {
        $this->class->givenTheClass($action);
        $this->representers[$class]->addAction(new ActionGenerator($action));
    }

    public function givenIHaveTheTheRenderer_For($callable, $class) {
        $this->representers[$class]->setRenderer($callable);
    }

    public function givenIAddedAnAction_ForTheProperty_Of($action, $property, $class) {
        $this->representers[$class]->addPropertyAction($property, new PropertyActionGenerator($action));
    }

} 