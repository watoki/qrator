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
        $this->representers[$class] = new GenericActionRepresenter($this->spec->factory);
        $this->registry->register($class, $this->representers[$class]);
    }

    public function givenIRegisteredAnEntityRepresenterFor($class) {
        $this->representers[$class] = new GenericEntityRepresenter($this->spec->factory);
        $this->registry->register($class, $this->representers[$class]);
    }

    public function givenIAddedTheQuery_ToTheRepresenterOf($query, $class) {
        $this->class->givenTheClass($query);
        $this->representers[$class]->addQuery(new ActionGenerator($query));
    }

    public function givenIAddedTheCommand_ToTheRepresenterOf($command, $class) {
        $this->class->givenTheClass($command);
        $this->representers[$class]->addCommand(new ActionGenerator($command));
    }

    public function givenIHaveTheTheRenderer_For($callable, $class) {
        $this->representers[$class]->setRenderer($callable);
    }

    public function givenIAddedAQuery_ForTheProperty_Of($query, $property, $class) {
        $this->representers[$class]->addPropertyQuery($property, new PropertyActionGenerator($query));
    }

    public function givenIAddedACommand_ForTheProperty_Of($query, $property, $class) {
        $this->representers[$class]->addPropertyCommand($property, new PropertyActionGenerator($query));
    }

} 