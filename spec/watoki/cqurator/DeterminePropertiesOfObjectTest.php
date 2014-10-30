<?php
namespace spec\watoki\cqurator;

use watoki\cqurator\representer\GenericRepresenter;
use watoki\scrut\Specification;

/**
 * @property \spec\watoki\cqurator\fixtures\ClassFixture class <-
 */
class DeterminePropertiesOfObjectTest extends Specification {

    function testFindPublicPropertiesAndAccessors() {
        $this->class->givenTheClass_WithTheBody('fields\one\SomeClass', '
            public $public = "one";
            public $publicAndGetter = "two";
            public $publicAndSetter = "three";
            private $private = "four";

            function getPublicAndGetter() { return "five"; }
            function setPublicAndSetter() { }
            function getPrivate() { return "six"; }
            function getGetter() { return "seven"; }
            function setSetter() { }
        ');

        $this->whenIDetermineThePropertiesOf('fields\one\SomeClass');
        $this->thenThereShouldBe_Properties(6);
        $this->thenTheValueOf_ShouldBe('public', 'one');
        $this->thenTheValueOf_ShouldBe('publicAndGetter', 'two');
        $this->thenTheValueOf_ShouldBe('publicAndSetter', 'three');
        $this->thenTheValueOf_ShouldBe('private', 'six');
        $this->thenTheValueOf_ShouldBe('getter', 'seven');
        $this->thenTheValueOf_ShouldNotBeGettable('setter');
    }

    ##################################################################################################

    /** @var \watoki\cqurator\representer\Property[] */
    private $properties;

    private function whenIDetermineThePropertiesOf($class) {
        $representer = new GenericRepresenter();
        $this->properties = $representer->getProperties(new $class);
    }

    private function thenThereShouldBe_Properties($int) {
        $this->assertCount($int, $this->properties);
    }

    private function thenTheValueOf_ShouldBe($name, $value) {
        $this->assertEquals($value, $this->properties[$name]->get());
    }

    private function thenTheValueOf_ShouldNotBeGettable($name) {
        try {
            $this->properties[$name]->get();
            $this->fail("Should have thrown an Exception");
        } catch (\Exception $e) {}
    }

} 