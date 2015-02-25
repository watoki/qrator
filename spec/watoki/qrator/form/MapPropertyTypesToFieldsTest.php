<?php
namespace spec\watoki\qrator\form;

use watoki\qrator\form\fields\ArrayField;
use watoki\qrator\form\fields\CheckboxField;
use watoki\qrator\form\fields\DateTimeField;
use watoki\qrator\form\fields\SelectEntityField;
use watoki\qrator\form\fields\InputField;
use watoki\qrator\representer\generic\GenericActionRepresenter;
use watoki\scrut\Specification;

/**
 * @property \spec\watoki\reflect\fixtures\ClassFixture class <-
 */
class MapPropertyTypesToFieldsTest extends Specification {

    function testUnknown() {
        $this->class->givenTheClass_WithTheBody('mapUnknown\Action', '
            public $unknown;
        ');
        $this->whenIGetTheFieldsOf('mapUnknown\Action');
        $this->then_ShouldBeA('unknown', InputField::class);
    }

    function testString() {
        $this->class->givenTheClass_WithTheBody('mapString\Action', '
            /** @var string */
            public $string;
        ');
        $this->whenIGetTheFieldsOf('mapString\Action');
        $this->then_ShouldBeA('string', InputField::class);
    }

    function testArray() {
        $this->class->givenTheClass_WithTheBody('mapArray\Action', '
            /** @var array|string[] */
            public $array;
        ');
        $this->whenIGetTheFieldsOf('mapArray\Action');
        $this->then_ShouldBeA('array', ArrayField::class);
        $this->thenTheInnerFieldOf_ShouldBeA('array', InputField::class);
    }

    function testSelectEntity() {
        $this->class->givenTheClass_WithTheBody('mapSelectEntity\Action', '
            /** @var string|\DateTime-ID */
            public $entity;
        ');
        $this->whenIGetTheFieldsOf('mapSelectEntity\Action');
        $this->then_ShouldBeA('entity', SelectEntityField::class);
        $this->thenTheTargetOf_ShouldBe('entity', \DateTime::class);
    }

    function testMultiProperty() {
        $this->class->givenTheClass_WithTheBody('mapMulti\Action', '
            /** @var \DateTime-ID|string */
            public $multi;
        ');
        $this->whenIGetTheFieldsOf('mapMulti\Action');
        $this->then_ShouldBeA('multi', SelectEntityField::class);
    }
    
    function testDateTime() {
        $this->class->givenTheClass_WithTheBody('mapDateTime\Action', '
            /** @var \DateTime */
            public $date;
        ');
        $this->whenIGetTheFieldsOf('mapDateTime\Action');
        $this->then_ShouldBeA('date', DateTimeField::class);
    }

    function testBoolean() {
        $this->class->givenTheClass_WithTheBody('mapBoolean\Action', '
            /** @var bool */
            public $boolean;
        ');
        $this->whenIGetTheFieldsOf('mapBoolean\Action');
        $this->then_ShouldBeA('boolean', CheckboxField::class);
    }

    ##################################################################################################

    /** @var \watoki\qrator\form\Field[] */
    private $fields;

    private function whenIGetTheFieldsOf($actionClass) {
        $representer = new GenericActionRepresenter($actionClass, $this->factory);
        $this->fields = $representer->getFields($representer->create());
    }

    private function find($property) {
        foreach ($this->fields as $field) {
            if ($field->getName() == $property) {
                return $field;
            }
        }
        throw new \Exception("[$property] not found");
    }

    private function then_ShouldBeA($property, $fieldClass) {
        $this->assertInstanceOf($fieldClass, $this->find($property));
    }

    private function thenTheInnerFieldOf_ShouldBeA($property, $fieldClass) {
        $field = $this->find($property);
        if (!($field instanceof ArrayField)) {
            $this->fail("No an ArrayField");
        }
        $this->assertInstanceOf($fieldClass, $field->getInnerField());
    }

    private function thenTheTargetOf_ShouldBe($property, $class) {
        $field = $this->find($property);
        if (!($field instanceof SelectEntityField)) {
            $this->fail("No a SelectEntityField");
        }
        $this->assertEquals($class, $field->getEntityClass());
    }

}