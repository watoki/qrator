<?php
namespace spec\watoki\qrator;

use watoki\collections\Map;
use watoki\factory\Factory;
use watoki\factory\providers\CallbackProvider;
use watoki\qrator\representer\property\ObjectProperty;
use watoki\qrator\representer\property\PublicProperty;
use watoki\scrut\Specification;

/**
 * Instead of defining a class for each Action, they can be derived from methods signatures.
 *
 * Then hanlder of those actions then destructs the objects onto the parameters.
 *
 * @property \spec\watoki\qrator\fixtures\ClassFixture class <-
 * @property \spec\watoki\qrator\fixtures\DispatcherFixture dispatcher <-
 * @property \spec\watoki\qrator\fixtures\RegistryFixture registry <-
 */
class DeriveActionsFromMethodsTest extends Specification {

    protected function background() {
        $this->class->givenTheClass_WithTheBody('construct\SomeClass', '
            function someMethod($one, $two = null) {
                return $one . " " . $two;
            }
        ');
    }

    function testConstructFromMethod() {
        $this->whenIConstructAnActionFromTheMethod_Of('someMethod', 'construct\SomeClass');
        $this->thenTheActionShouldHaveTheProperties(['one', 'two']);
        $this->thenTheActionShouldHaveTheName('Some Method');
        $this->thenTheActionShouldHaveTheClass('construct\SomeClass__someMethod');
    }

    function testInvokeMethodWithArgumentsOfAction() {
        $this->whenIConstructAnActionFromTheMethod_Of('someMethod', 'construct\SomeClass');
        $this->whenIExecuteTheActionWith(['one' => 'uno', 'two' => 'dos']);
        $this->thenItShouldReturn('uno dos');
    }

    function testDetermineRequiredProperties() {
        $this->whenIConstructAnActionFromTheMethod_Of('someMethod', 'construct\SomeClass');
        $this->thenProperty_ShouldBeRequired('one');
        $this->thenProperty_ShouldBeOptional('two');
    }

    #################################################################################

    /** @var \watoki\qrator\ActionRepresenter */
    private $representer;

    private $returned;

    private function whenIConstructAnActionFromTheMethod_Of($method, $class) {
        $this->representer = new DerivedActionRepresenter($class, $method, $this->factory);
    }

    private function whenIExecuteTheActionWith($args) {
        $this->returned = $this->representer->execute($this->representer->create(new Map($args)));
    }

    private function thenTheActionShouldHaveTheProperties($properties) {
        $this->assertEquals($properties, $this->representer->getProperties(new \StdClass())->keys()->toArray());
    }

    private function thenTheActionShouldHaveTheName($string) {
        $this->assertEquals($string, $this->representer->getName('anything'));
    }

    private function thenTheActionShouldHaveTheClass($string) {
        $this->assertEquals($string, $this->representer->getClass());
    }

    private function thenItShouldReturn($string) {
        $this->assertEquals($string, $this->returned);
    }

    private function thenProperty_ShouldBeRequired($string) {
        $this->assertTrue($this->representer->getProperties()[$string]->isRequired());
    }

    private function thenProperty_ShouldBeOptional($string) {
        $this->assertFalse($this->representer->getProperties()[$string]->isRequired());
    }

}

class DerivedActionRepresenter extends \watoki\qrator\representer\GenericActionRepresenter{

    /** @var \ReflectionMethod */
    private $method;

    /** @var Factory */
    private $factory;

    public function __construct($className, $methodName, Factory $factory) {
        parent::__construct($className, $factory);
        $this->factory = $factory;
        $this->method = new \ReflectionMethod($className, $methodName);

        $factory->setProvider($this->getClass(), new CallbackProvider(function () {
            return new GenericAction();
        }));
    }

    public function execute($object) {
        $handler = $this->factory->getInstance($this->method->getDeclaringClass()->getName());
        $properties = $this->getProperties($object);

        $args = [];
        foreach ($this->method->getParameters() as $parameter) {
            $args[] = $properties[$parameter->getName()]->get();
        }
        return $this->method->invokeArgs($handler, $args);
    }

    public function getClass() {
        return parent::getClass() . '__' . $this->method->getName();
    }

    /**
     * @return string
     */
    public function getName() {
        return ucfirst(preg_replace('/([a-z])([A-Z])/', '$1 $2', $this->method->getShortName()));
    }

    /**
     * @param object|null $object Object or class reference
     * @return Map|ObjectProperty[]  indexed by property name
     */
    public function getProperties($object = null) {
        $properties = new Map();
        foreach ($this->method->getParameters() as $parameter) {
            $property = new PublicProperty($object, $parameter->getName(), !$parameter->isDefaultValueAvailable());
            $properties->set($parameter->getName(), $property);
        }
        return $properties;
    }
}

class GenericAction {

}