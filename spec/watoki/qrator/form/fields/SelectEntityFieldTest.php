<?php
namespace spec\watoki\qrator\form\fields;

use watoki\qrator\form\fields\SelectEntityField;
use watoki\scrut\Specification;

/**
 * @property \spec\watoki\qrator\fixtures\FieldFixture field <-
 * @property \spec\watoki\qrator\fixtures\RegistryFixture registry <-
 * @property \spec\watoki\qrator\fixtures\DispatcherFixture dispatcher <-
 * @property \spec\watoki\reflect\fixtures\ClassFixture class <-
 */
class SelectEntityFieldTest extends Specification {

    protected function background() {
        $this->class->givenTheClass('ListEntity');
        $this->dispatcher->givenIAddedTheClosure_AsHandlerFor(function () {
            return [];
        }, 'ListEntity');
    }

    function testNoListActionGiven() {
        $this->class->givenTheClass('EntityClass');
        $this->registry->givenIRegisteredAnEntityRepresenterFor('EntityClass');
        $this->givenASelectEntityField_WithTheEntity('test', 'EntityClass');

        $this->field->whenITryToRenderTheField();
        $this->field->try->thenTheException_ShouldBeThrown('Cannot select [EntityClass]: list action not set.');
    }

    function testEmptyEntityList() {
        $this->class->givenTheClass('EntityClass');
        $this->registry->givenIRegisteredAnEntityRepresenterFor('EntityClass');
        $this->registry->givenIHaveSet_AsTheListActionFor('ListEntity', 'EntityClass');
        $this->givenASelectEntityField_WithTheEntity('test', 'EntityClass');

        $this->field->whenIRenderTheField();
        $this->field->thenTheOutputShouldBe(
            '<label for="test">Test</label>
            <select id="test" class="form-control" name="args[test]">
            </select>');
    }

    function testShowEntityOptions() {
        $this->class->givenTheClass_WithTheBody('NamedEntity', '
            function __construct($name) { $this->name = $name; }
            function getId() { return strtolower($this->name); }
        ');
        $this->registry->givenIRegisteredAnEntityRepresenterFor('NamedEntity');
        $this->registry->givenIHaveSet_AsTheListActionFor('ListEntity', 'NamedEntity');
        $this->givenASelectEntityField_WithTheEntity('test', 'NamedEntity');

        $this->class->givenTheClass_WithTheBody('EntityHandler', '
            function listEntity() {
                return [
                    new NamedEntity("Bart"),
                    new NamedEntity("Lisa"),
                ];
            }
        ');
        $this->dispatcher->givenIAddedTheClass_AsHandlerFor('EntityHandler', 'ListEntity');

        $this->field->whenIRenderTheField();
        $this->field->thenTheOutputShouldBe(
            '<label for="test">Test</label>
            <select id="test" class="form-control" name="args[test]">
                <option value="bart">Named Entity [name:Bart|id:bart]</option>
                <option value="lisa">Named Entity [name:Lisa|id:lisa]</option>
            </select>');
    }

    function testEntitiesWithIdentifierObjects() {
        $this->class->givenTheClass_WithTheBody('SelectWithIdObject\EntityId', '
            function __construct($id) { $this->id = $id; }
            function __toString() { return $this->id; }'
        );
        $this->class->givenTheClass_WithTheBody('SelectWithIdObject\Entity', '
            function __construct(EntityId $id) { $this->id = $id; }
            function __toString() { return ucfirst((string)$this->id); }
        ');
        $this->registry->givenIRegisteredAnEntityRepresenterFor('SelectWithIdObject\Entity');
        $this->registry->givenIHaveSet_AsTheListActionFor('ListEntity', 'SelectWithIdObject\Entity');

        $this->givenASelectEntityField_WithTheEntity('test', 'SelectWithIdObject\Entity');

        $this->class->givenTheClass_WithTheBody('SelectWithIdObject\Handler', '
            function listEntity() {
                return [
                    new Entity(new EntityId("bart")),
                    new Entity(new EntityId("lisa")),
                ];
            }
        ');
        $this->dispatcher->givenIAddedTheClass_AsHandlerFor('SelectWithIdObject\Handler', 'ListEntity');

        $this->field->whenIRenderTheField();
        $this->field->thenTheOutputShouldBe(
            '<label for="test">Test</label>
            <select id="test" class="form-control" name="args[test]">
                <option value="bart">Bart</option>
                <option value="lisa">Lisa</option>
            </select>');
    }

    ################################################################################################

    private function givenASelectEntityField_WithTheEntity($name, $entity) {
        $this->registry->givenIRegisteredAnActionRepresenterFor($entity);
        $this->field->givenTheField(new SelectEntityField($name, $entity, $this->registry->registry));
    }

} 