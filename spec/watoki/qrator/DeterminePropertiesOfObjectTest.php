<?php
namespace spec\watoki\qrator;

use watoki\collections\Map;
use watoki\qrator\representer\generic\GenericActionRepresenter;
use watoki\qrator\representer\Property;
use watoki\qrator\representer\property\types\ArrayType;
use watoki\qrator\representer\property\types\ClassType;
use watoki\qrator\representer\property\types\FloatType;
use watoki\qrator\representer\property\types\IdentifierObjectType;
use watoki\qrator\representer\property\types\IdentifierType;
use watoki\qrator\representer\property\types\IntegerType;
use watoki\qrator\representer\property\types\MultiType;
use watoki\qrator\representer\property\types\NullableType;
use watoki\qrator\representer\property\types\StringType;
use watoki\scrut\Specification;

/**
 * @property \spec\watoki\qrator\fixtures\ClassFixture class <-
 */
class DeterminePropertiesOfObjectTest extends Specification {

    function testFindPublicProperties() {
        $this->class->givenTheClass_WithTheBody('publicProperties\SomeClass', '
            public $public = "one";
            private $private = "four";
        ');

        $this->whenIDetermineThePropertiesOf('publicProperties\SomeClass');
        $this->thenThereShouldBe_Properties(1);
        $this->then_ShouldBeGettable('public');
        $this->then_ShouldBeSettable('public');
        $this->thenTheValueOf_ShouldBe('public', 'one');
    }

    function testFindAccessorProperties() {
        $this->class->givenTheClass_WithTheBody('accessors\SomeClass', '
            function getGetter() { return "seven"; }
            function isBoolean() { return true; }
            function setSetter($a) { }
            function getBoth() {}
            function setBoth($a) {}
            function notAnAccessor() {}
            function getNeither ($becauseOfTheParameter) {}
        ');

        $this->whenIDetermineThePropertiesOf('accessors\SomeClass');
        $this->thenThereShouldBe_Properties(4);
        $this->then_ShouldBeGettable('getter');
        $this->then_ShouldNotBeSettable('getter');
        $this->then_ShouldBeSettable('setter');
        $this->then_ShouldNotBeGettable('setter');
        $this->then_ShouldBeGettable('boolean');
        $this->thenTheValueOf_ShouldBe('getter', 'seven');
        $this->thenTheValueOf_ShouldBe('boolean', true);
    }

    function testPublicTrumpAccessor() {
        $this->class->givenTheClass_WithTheBody('both\SomeClass', '
            public $publicAndGetter = "public";
            function getPublicAndGetter() { return "getter"; }
        ');

        $this->whenIDetermineThePropertiesOf('both\SomeClass');
        $this->thenThereShouldBe_Properties(1);
        $this->thenTheValueOf_ShouldBe('publicAndGetter', 'public');
    }

    function testFindConstructorProperties() {
        $this->class->givenTheClass_WithTheBody('constructor\ClassWithConstructor', '
            function __construct($one = null) {}
        ');

        $this->whenIDetermineThePropertiesOf('constructor\ClassWithConstructor');
        $this->thenThereShouldBe_Properties(1);
        $this->then_ShouldBeSettable('one');
        $this->then_ShouldNotBeGettable('one');
    }

    function testMergeProperties() {
        $this->class->givenTheClass_WithTheBody('mergeProperties\SomeClass', '
            public $one;
            function __construct($one = null, $two = null) {}
            function getTwo() {}
        ');

        $this->whenIDetermineThePropertiesOf('mergeProperties\SomeClass');
        $this->thenThereShouldBe_Properties(2);
        $this->then_ShouldBeGettable('one');
        $this->then_ShouldBeGettable('two');
    }

    function testRequiredProperties() {
        $this->class->givenTheClass_WithTheBody('required\SomeClass', '
            public $two;
            public $four;
            function __construct($one, $two, $three = null) {}
        ');
        $this->givenTheActionArgument_Is('one', 'uno');
        $this->givenTheActionArgument_Is('two', 'dos');

        $this->whenIDetermineThePropertiesOf('required\SomeClass');
        $this->thenThereShouldBe_Properties(4);

        $this->then_ShouldBeRequired('one');
        $this->then_ShouldBeRequired('two');
        $this->then_ShouldBeOptional('three');
        $this->then_ShouldBeOptional('four');
    }

    function testPublicPropertyTypes() {
        $this->class->givenTheClass_WithTheBody('publicTypes\SomeClass', '
            /** @var int */
            public $int;

            /** @var string */
            public $string;

            /** @var \DateTime */
            public $class;

            public $unknown;
        ');

        $this->whenIDetermineThePropertiesOf('publicTypes\SomeClass');
        $this->thenThereShouldBe_Properties(4);
        $this->then_ShouldHaveTheType('int', IntegerType::class);
        $this->then_ShouldHaveTheType('string', StringType::class);
        $this->then_ShouldHaveTheType('class', ClassType::class);
        $this->thenTheClassOf_ShouldBe('class', \DateTime::class);
        $this->then_ShouldHaveNoType('unknown');
    }

    function testAccessorTypes() {
        $this->class->givenTheClass_WithTheBody('accessorTypes\SomeClass', '
            /** @return long */
            function getOne() {}

            /** @param float $two */
            function setTwo($two) {}

            function setThree(\DateTime $three) {}
        ');

        $this->whenIDetermineThePropertiesOf('accessorTypes\SomeClass');
        $this->thenThereShouldBe_Properties(3);
        $this->then_ShouldHaveTheType('one', IntegerType::class);
        $this->then_ShouldHaveTheType('two', FloatType::class);
        $this->then_ShouldHaveTheType('three', ClassType::class);
    }

    function testConstructorTypes() {
        $this->class->givenTheClass_WithTheBody('constructorTypes\SomeClass', '
            /** @param integer $one */
            function __construct($one, \DateTime $two) {}
        ');
        $this->givenTheActionArgument_Is('one', 'uno');
        $this->givenTheActionArgument_Is('two', 'now');

        $this->whenIDetermineThePropertiesOf('constructorTypes\SomeClass');
        $this->thenThereShouldBe_Properties(2);
        $this->then_ShouldHaveTheType('one', IntegerType::class);
        $this->then_ShouldHaveTheType('two', ClassType::class);
    }

    function testComplexTypes() {
        $this->class->givenTheClass_WithTheBody('ComplexTypes\SomeClass', '
            /** @var null|int */
            public $int;

            /** @var array|string[] */
            public $array;

            /** @var int|string */
            public $multi;

            /** @var string */
            public $merged;

            /** @return double */
            function getMerged() {}
        ');

        $this->whenIDetermineThePropertiesOf('ComplexTypes\SomeClass');
        $this->thenThereShouldBe_Properties(4);

        $this->then_ShouldHaveTheType('int', NullableType::class);
        $this->thenTheInnerTypeOf_ShouldBe('int', IntegerType::class);

        $this->then_ShouldHaveTheType('array', ArrayType::class);
        $this->thenTheItemTypeOf_ShouldBe('array', StringType::class);

        $this->then_ShouldHaveTheType('multi', MultiType::class);
        $this->thenTheTypesOf_ShouldBe('multi', [IntegerType::class, StringType::class]);

        $this->then_ShouldHaveTheType('merged', MultiType::class);
        $this->thenTheTypesOf_ShouldBe('merged', [StringType::class, FloatType::class]);
    }

    function testIdentifierType() {
        $this->class->givenTheClass('IdentifierType\SomeEntity');
        $this->class->givenTheClass_WithTheBody('IdentifierType\SomeEntityId', 'function __toString() {}');
        $this->class->givenTheClass_WithTheBody('IdentifierType\elsewhere\SomeEntityId', '
            const TARGET = \IdentifierType\SomeEntity::class;
        ');
        $this->class->givenTheClass_WithTheBody('IdentifierType\SomeClass', '
            /** @var SomeEntity-ID */
            public $suffixed;

            /** @var SomeEntity-Id */
            public $caseInsensitiveSuffix;

            /** @var \IdentifierType\SomeEntityId */
            public $targetConst;

            /** @var SomeEntityId */
            public $sameNameSpace;

            function __construct(SomeEntityId $inConstructor = null) {}
        ');

        $this->whenIDetermineThePropertiesOf('IdentifierType\SomeClass');
        $this->thenThereShouldBe_Properties(5);

        $this->then_ShouldBeAndIdentifierFor('suffixed', 'IdentifierType\SomeEntity');
        $this->then_ShouldBeAndIdentifierFor('caseInsensitiveSuffix', 'IdentifierType\SomeEntity');
        $this->then_ShouldBeAndIdentifierObjectFor('targetConst', 'IdentifierType\SomeEntity');
        $this->then_ShouldBeAndIdentifierObjectFor('sameNameSpace', 'IdentifierType\SomeEntity');
        $this->then_ShouldBeAndIdentifierObjectFor('inConstructor', 'IdentifierType\SomeEntity');
    }

    ##################################################################################################

    private $args = [];

    private $object;

    /** @var Property[] */
    private $properties;

    protected function setUp() {
        parent::setUp();
    }

    private function whenIDetermineThePropertiesOf($class) {
        $representer = new GenericActionRepresenter($class, $this->factory);
        $this->object = $representer->create(new Map($this->args));
        $this->properties = $representer->getProperties($this->object);
        return true;
    }

    private function thenThereShouldBe_Properties($int) {
        $this->assertCount($int, $this->properties);
    }

    private function thenTheValueOf_ShouldBe($name, $value) {
        $this->assertEquals($value, $this->properties[$name]->get($this->object));
    }

    private function then_ShouldNotBeGettable($name) {
        $this->assertFalse($this->properties[$name]->canGet(), "$name should not be gettable");
    }

    private function then_ShouldBeSettable($name) {
        $this->assertTrue($this->properties[$name]->canSet(), "$name should be settable");
    }

    private function then_ShouldNotBeSettable($name) {
        $this->assertFalse($this->properties[$name]->canSet(), "$name should not be settable");
    }

    private function then_ShouldBeGettable($name) {
        $this->assertTrue($this->properties[$name]->canGet(), "$name should be gettable");
    }

    private function givenTheActionArgument_Is($key, $value) {
        $this->args[$key] = $value;
    }

    private function then_ShouldBeRequired($name) {
        $this->assertTrue($this->properties[$name]->isRequired(), "$name should be required");
    }

    private function then_ShouldBeOptional($name) {
        $this->assertFalse($this->properties[$name]->isRequired(), "$name should be optional");
    }

    private function then_ShouldHaveTheType($name, $type) {
        $this->assertInstanceOf($type, $this->properties[$name]->type());
    }

    private function then_ShouldHaveNoType($name) {
        $this->assertEquals(null, $this->properties[$name]->type());
    }

    private function thenTheClassOf_ShouldBe($name, $class) {
        $type = $this->properties[$name]->type();
        if (!($type instanceof ClassType)) {
            $this->fail("Not a ClassType: $name");
        }
        $this->assertEquals($class, $type->getClass());
    }

    private function thenTheInnerTypeOf_ShouldBe($name, $expectedType) {
        $type = $this->properties[$name]->type();
        if (!($type instanceof NullableType)) {
            $this->fail("Not a NullableType: $name");
        }
        $this->assertInstanceOf($expectedType, $type->getType());
    }

    private function thenTheItemTypeOf_ShouldBe($name, $expectedType) {
        $type = $this->properties[$name]->type();
        if (!($type instanceof ArrayType)) {
            $this->fail("Not an ArrayType: $name");
        }
        $this->assertInstanceOf($expectedType, $type->getItemType());
    }

    private function thenTheTypesOf_ShouldBe($name, $types) {
        $type = $this->properties[$name]->type();
        if (!($type instanceof MultiType)) {
            $this->fail("Not an MultiType: $name");
        }
        $this->assertEquals($types, array_map(function ($type) {
            return get_class($type);
        }, $type->getTypes()));
    }

    private function thenTheTargetOf_ShouldBe($name, $class) {
        $type = $this->properties[$name]->type();
        if (!($type instanceof IdentifierType)) {
            $this->fail("Not a IdentifierType: $name");
        }
        $this->assertEquals($class, $type->getTarget());
    }

    private function then_ShouldBeAndIdentifierFor($property, $target) {
        $this->then_ShouldHaveTheType($property, IdentifierType::class);
        $this->thenTheTargetOf_ShouldBe($property, $target);
    }

    private function then_ShouldBeAndIdentifierObjectFor($property, $target) {
        $this->then_ShouldHaveTheType($property, IdentifierObjectType::class);
        $this->thenTheTargetOf_ShouldBe($property, $target);
    }

} 