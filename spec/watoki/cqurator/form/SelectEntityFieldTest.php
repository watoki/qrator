<?php
namespace spec\watoki\cqurator\form;

use watoki\cqurator\form\fields\SelectEntityField;
use watoki\scrut\Specification;

/**
 * @property \spec\watoki\cqurator\fixtures\FieldFixture field <-
 * @property \spec\watoki\cqurator\fixtures\RegistryFixture registry <-
 * @property \spec\watoki\cqurator\fixtures\DispatcherFixture dispatcher <-
 * @property \spec\watoki\cqurator\fixtures\ClassFixture class <-
 */
class SelectEntityFieldTest extends Specification {

    protected function background() {
        $this->class->givenTheClass('EntityClass');
    }

    function testNoListQueryGiven() {
        $this->givenASelectEntityField_For('test', 'EntityClass');
        $this->field->whenITryToRenderTheField();
        $this->field->try->thenTheException_ShouldBeThrown('The Representer of [EntityClass] must provide a listing query.');
    }

    function testEmptyEntityList() {
        $this->givenASelectEntityField_For('test', 'EntityClass');

        $this->registry->givenIRegisteredAnEntityRepresenterFor('EntityClass');
        $this->givenISetTheListQueryOf_To('EntityClass', 'ListEntity');

        $this->field->whenIRenderTheField();
        $this->field->thenTheOutputShouldBe(
            '<select name="args[test]">
            </select>');
    }

    function testShowEntityOptions() {
        $this->class->givenTheClass_WithTheBody('NamedEntity', '
            function __construct($name) { $this->name = $name; }
            function getId() { return strtolower($this->name); }
        ');
        $this->givenASelectEntityField_For('test', 'NamedEntity');

        $this->registry->givenIRegisteredAnEntityRepresenterFor('NamedEntity');
        $this->givenISetTheListQueryOf_To('NamedEntity', 'ListEntity');

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
            '<select name="args[test]">
                <option value="bart">NamedEntity[name:Bart|id:bart]</option>
                <option value="lisa">NamedEntity[name:Lisa|id:lisa]</option>
            </select>');
    }

    ################################################################################################

    private function givenASelectEntityField_For($name, $class) {
        $this->field->givenTheField(new SelectEntityField($name, $class,
            $this->registry->registry, $this->dispatcher->dispatcher));
    }

    private function givenISetTheListQueryOf_To($entity, $query) {
        $this->class->givenTheClass($query);
        $this->registry->representers[$entity]->setListQuery(new $query);
    }

} 