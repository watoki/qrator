<?php
namespace spec\watoki\qrator\form;

use watoki\qrator\form\fields\ArrayField;
use watoki\qrator\form\fields\SelectEntityField;
use watoki\qrator\form\fields\StringField;
use watoki\qrator\representer\generic\GenericActionRepresenter;
use watoki\qrator\RepresenterRegistry;
use watoki\scrut\Specification;

/**
 * @property \spec\watoki\qrator\fixtures\ClassFixture class <-
 */
class MapPropertyTypesToFieldsTest extends Specification {

    function testString() {
        $this->class->givenTheClass_WithTheBody('mapString\Action', '
            /** @var string */
            public $string;
        ');
        $this->whenIGetTheFieldsOf('mapString\Action');
        $this->then_ShouldBeA('string', StringField::class);
    }

    function testArray() {
        $this->class->givenTheClass_WithTheBody('mapArray\Action', '
            /** @var array|string[] */
            public $array;
        ');
        $this->whenIGetTheFieldsOf('mapArray\Action');
        $this->then_ShouldBeA('array', ArrayField::class);
        $this->thenTheInnerFieldOf_ShouldBeA('array', StringField::class);
    }

    function testSelectEntity() {
        $this->class->givenTheClass_WithTheBody('mapSelectEntity\Action', '
            /** @var \DateTime-ID */
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

    ##################################################################################################

    /** @var \watoki\qrator\form\Field[] */
    private $fields;

    private function whenIGetTheFieldsOf($actionClass) {
        $representer = new GenericActionRepresenter($actionClass, $this->factory, new RepresenterRegistry($this->factory));
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