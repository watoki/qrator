<?php
namespace spec\watoki\qrator;

use watoki\qrator\web\ExecuteResource;
use watoki\scrut\Specification;

/**
 * @property \spec\watoki\qrator\fixtures\RegistryFixture registry <-
 * @property \spec\watoki\qrator\fixtures\DispatcherFixture dispatcher <-
 * @property \spec\watoki\reflect\fixtures\ClassFixture class <-
 * @property \spec\watoki\qrator\fixtures\ResourceFixture resource <-
 */
class ShowPropertiesTest extends Specification {

    public function background() {
        $this->class->givenTheClass('MyAction');
    }

    function testNoProperties() {
        $this->dispatcher->givenIAddedTheClosure_AsHandlerFor(function () {
            return new \StdClass;
        }, 'MyAction');

        $this->whenIShowTheResultsOf('MyAction');
        $this->thenThereShouldBeNoProperties();
    }

    function testDisplayDynamicProperties() {
        $this->dispatcher->givenIAddedTheClosure_AsHandlerFor(function () {
            $object = new \StdClass();
            $object->propertyOne = 'valueOne';
            $object->propertyTwo = 'valueTwo';
            return $object;
        }, 'MyAction');

        $this->whenIShowTheResultsOf('MyAction');
        $this->thenThereShouldBe_Properties(2);
        $this->thenProperty_ShouldHaveTheName_AndCaption(1, 'propertyOne', 'valueOne');
        $this->thenProperty_ShouldHaveTheLabel(1, 'Property One');
    }

    function testReadPropertiesFromGetters() {
        $this->class->givenTheClass_WithTheBody('getters\MyClass', '
            public $zero = "null";
            function getOne() { return "uno";}
            function getTwo() { return "dos"; }
            function setThree() { }
            private function getNotMe() {}
        ');
        $this->class->givenTheClass_WithTheBody('getters\MyHandler', '
            function myAction() {
                return new MyClass();
            }
        ');
        $this->dispatcher->givenIAddedTheClass_AsHandlerFor('getters\MyHandler', 'MyAction');

        $this->whenIShowTheResultsOf('MyAction');
        $this->thenThereShouldBe_Properties(3);
        $this->thenProperty_ShouldHaveTheName_AndCaption(1, 'zero', 'null');
        $this->thenProperty_ShouldHaveTheName_AndCaption(2, 'one', 'uno');
        $this->thenProperty_ShouldHaveTheName_AndCaption(3, 'two', 'dos');
    }

    function testPropertyWithBooleanValues() {
        $this->class->givenTheClass_WithTheBody('booleanValue\MyClass', '
            public $true = true;
            public $false = false;
        ');
        $this->class->givenTheClass_WithTheBody('booleanValue\MyHandler', '
            function myAction() {
                return new MyClass();
            }
        ');
        $this->dispatcher->givenIAddedTheClass_AsHandlerFor('booleanValue\MyHandler', 'MyAction');

        $this->whenIShowTheResultsOf('MyAction');
        $this->thenThereShouldBe_Properties(2);
        $this->thenProperty_ShouldHaveTheName_AndCaption(1, 'true', 'Yes');
        $this->thenProperty_ShouldHaveTheName_AndCaption(2, 'false', 'No');
    }

    function testRenderObjectProperties() {
        $this->dispatcher->givenIAddedTheClosure_AsHandlerFor(function () {
            $object = new \StdClass;
            $object->one = new \DateTime('2012-03-04 15:16');
            return $object;
        }, 'MyAction');

        $this->registry->givenIRegisteredAnEntityRepresenterFor('DateTime');
        $this->registry->givenIHaveTheTheRenderer_For(function (\DateTime $d) {
            return $d->format('Y-m-d H:i');
        }, 'DateTime');

        $this->whenIShowTheResultsOf('MyAction');
        $this->thenThereShouldBe_Properties(1);
        $this->thenProperty_ShouldHaveTheName_AndCaption(1, 'one', '2012-03-04 15:16');
    }

    function testShowCollectionObjectProperty() {
        $this->class->givenTheClass_WithTheBody('collectionObject\SomeEntity', '
            function getArray() { return new \watoki\collections\Liste([
                ["foo" => "bar"],
                ["bar" => "baz"],
            ]); }
        ');
        $this->class->givenTheClass_WithTheBody('collectionObject\MyHandler', '
            function myAction() {
                return new SomeEntity();
            }
        ');
        $this->dispatcher->givenIAddedTheClass_AsHandlerFor('collectionObject\MyHandler', 'MyAction');

        $this->whenIShowTheResultsOf('MyAction');
        $this->thenThereShouldBe_Properties(1);
        $this->thenProperty_ShouldHave_Value(1, 2);
        $this->thenProperty_ShouldHaveValue_WithTheCaption(1, 1, 'Array(    [foo] => bar)');
    }

    function testShowActionsForProperties() {
        $this->class->givenTheClass_WithTheBody('properties\OtherEntity', '
            public $id = "property";
        ');
        $this->class->givenTheClass_WithTheBody('properties\SomeEntity', '
            public $id = "entity";
            function __construct($one) { $this->one = $one; }
        ');
        $this->class->givenTheClass_WithTheBody('properties\MyHandler', '
            function myAction() {
                return new SomeEntity(new OtherEntity());
            }
        ');
        $this->dispatcher->givenIAddedTheClass_AsHandlerFor('properties\MyHandler', 'MyAction');

        $this->registry->givenIRegisteredAnEntityRepresenterFor('properties\OtherEntity');
        $this->registry->givenIAddedTheAction_ToTheRepresenterOf('SomeAction', 'properties\OtherEntity');
        $this->registry->givenIAddedTheAction_ToTheRepresenterOf('SomeAnother', 'properties\OtherEntity');

        $this->whenIShowTheResultsOf('MyAction');
        $this->thenThereShouldBe_Properties(2);
        $this->thenProperty_ShouldHave_Actions(1, 2);
        $this->thenProperty_ShouldHaveAction_WithTheCaption(1, 1, 'Some Action');
        $this->thenProperty_ShouldHaveAction_WithTheLinkTarget(1, 1, 'execute?action=SomeAction&args[id]=property');
        $this->thenProperty_ShouldHaveAction_WithTheCaption(1, 2, 'Some Another');
    }

    function testPropertyActions() {
        $this->class->givenTheClass_WithTheBody('propertyActions\SomeEntity', '
            public $id = "someID";
            function __construct($other) { $this->other = $other; }
        ');
        $this->class->givenTheClass_WithTheBody('propertyActions\OtherEntity', '
            public $id = "otherID";
        ');
        $this->class->givenTheClass_WithTheBody('propertyActions\MyHandler', '
            function myAction() {
                return new SomeEntity(new OtherEntity());
            }
        ');
        $this->dispatcher->givenIAddedTheClass_AsHandlerFor('propertyActions\MyHandler', 'MyAction');

        $this->class->givenTheClass('propertyActions\PropertyAction');
        $this->class->givenTheClass('propertyActions\PropertyAnother');
        $this->registry->givenIRegisteredAnEntityRepresenterFor('propertyActions\SomeEntity');

        $this->registry->givenIAddedAnAction_ForTheProperty_Of('propertyActions\PropertyAction', 'other', 'propertyActions\SomeEntity');
        $this->registry->givenIAddedAnAction_ForTheProperty_Of('propertyActions\PropertyAnother', 'other', 'propertyActions\SomeEntity');

        $this->whenIShowTheResultsOf('MyAction');
        $this->thenThereShouldBe_Properties(2);
        $this->thenProperty_ShouldHaveAction_WithTheCaption(1, 1, 'Property Action');
        $this->thenProperty_ShouldHaveAction_WithTheLinkTarget(1, 1, 'execute?action=propertyActions%5CPropertyAction&args[id]=someID&args[object]=otherID');
        $this->thenProperty_ShouldHaveAction_WithTheCaption(1, 2, 'Property Another');
        $this->thenProperty_ShouldHaveAction_WithTheLinkTarget(1, 2, 'execute?action=propertyActions%5CPropertyAnother&args[id]=someID&args[object]=otherID');
    }

    function testShowArrayProperty() {
        $this->class->givenTheClass_WithTheBody('arrayProperty\SomeEntity', '
            function getArray() { return [
                new \DateTime("2001-01-01"),
                new \DateTime("2002-02-02")
            ]; }
        ');
        $this->class->givenTheClass_WithTheBody('arrayProperty\MyHandler', '
            function myAction() {
                return new SomeEntity();
            }
        ');
        $this->dispatcher->givenIAddedTheClass_AsHandlerFor('arrayProperty\MyHandler', 'MyAction');

        $this->registry->givenIRegisteredAnEntityRepresenterFor(\DateTime::class);
        $this->registry->givenIHaveTheTheRenderer_For(function (\DateTime $d) {
            return $d->format('Y-m-d H:i');
        }, \DateTime::class);

        $this->whenIShowTheResultsOf('MyAction');
        $this->thenThereShouldBe_Properties(1);
        $this->thenProperty_ShouldHaveTheName(1, 'array');
        $this->thenProperty_ShouldHave_Value(1, 2);
        $this->thenProperty_ShouldHaveValue_WithTheCaption(1, 1, '2001-01-01 00:00');
        $this->thenProperty_ShouldHaveValue_WithTheCaption(1, 2, '2002-02-02 00:00');
    }

    function testPropertyActionsOfArrayProperty() {
        $this->class->givenTheClass_WithTheBody('arrayPropertyActions\SomeEntity', '
            public $id = "someID";
            function __construct($other) { $this->other = $other; }
        ');
        $this->class->givenTheClass_WithTheBody('arrayPropertyActions\OtherEntity', '
            public $id = "otherID";
            function __construct($id) { $this->id = $id; }
        ');
        $this->class->givenTheClass_WithTheBody('arrayPropertyActions\MyHandler', '
            function myAction() {
                return new SomeEntity([
                    new OtherEntity(42),
                    new OtherEntity(73)
                ]);
            }
        ');
        $this->dispatcher->givenIAddedTheClass_AsHandlerFor('arrayPropertyActions\MyHandler', 'MyAction');

        $this->class->givenTheClass('arrayPropertyActions\PropertyAction');
        $this->class->givenTheClass('arrayPropertyActions\PropertyAnother');
        $this->registry->givenIRegisteredAnEntityRepresenterFor('arrayPropertyActions\SomeEntity');

        $this->registry->givenIAddedAnAction_ForTheProperty_Of('arrayPropertyActions\PropertyAction', 'other', 'arrayPropertyActions\SomeEntity');
        $this->registry->givenIAddedAnAction_ForTheProperty_Of('arrayPropertyActions\PropertyAnother', 'other', 'arrayPropertyActions\SomeEntity');

        $this->whenIShowTheResultsOf('MyAction');
        $this->thenThereShouldBe_Properties(2);
        $this->thenValue_OfProperty_ShouldHaveAction_WithTheLinkTarget(1, 1, 1, 'execute?action=arrayPropertyActions%5CPropertyAction&args[id]=someID&args[object]=42');
        $this->thenValue_OfProperty_ShouldHaveAction_WithTheLinkTarget(2, 1, 1, 'execute?action=arrayPropertyActions%5CPropertyAction&args[id]=someID&args[object]=73');
    }

    ########################################################################################################

    private function whenIShowTheResultsOf($action) {
        $this->resource->whenIDo_With(function (ExecuteResource $resource) use ($action) {
            return $resource->doGet($action, $this->resource->args);
        }, ExecuteResource::class);
    }

    private function thenThereShouldBeNoProperties() {
        $this->resource->then_ShouldBe('entity/properties/isEmpty', true);
    }

    private function thenThereShouldBe_Properties($int) {
        $this->resource->thenThereShouldBe_Of($int, 'entity/properties/item');
    }

    private function thenProperty_ShouldHaveTheName_AndCaption($int, $name, $value) {
        $this->thenProperty_ShouldHaveTheName($int, $name);
        $int--;
        $this->resource->then_ShouldBe("entity/properties/item/$int/value/caption", $value);
    }

    private function thenValue_OfProperty_ShouldHaveAction_WithTheLinkTarget($valuePos, $propertyPos, $actionPos, $target) {
        $propertyPos--;
        $valuePos--;
        $actionPos--;
        $this->resource->then_ShouldBe("entity/properties/item/$propertyPos/value/$valuePos/actions/$actionPos/link/href", $target);
    }

    private function thenProperty_ShouldHaveTheName($int, $name) {
        $int--;
        $this->resource->then_ShouldBe("entity/properties/item/$int/name", $name);
    }

    private function thenProperty_ShouldHaveTheLabel($int, $name) {
        $int--;
        $this->resource->then_ShouldBe("entity/properties/item/$int/label", $name);
    }

    private function thenProperty_ShouldHave_Value($pos, $count) {
        $pos--;
        $this->resource->thenThereShouldBe_Of($count, "entity/properties/item/$pos/value");
    }

    private function thenProperty_ShouldHaveValue_WithTheCaption($propertyPos, $valuePos, $caption) {
        $propertyPos--;
        $valuePos--;
        $value = $this->resource->get("entity/properties/item/$propertyPos/value/$valuePos/caption");
        $this->assertEquals($caption, str_replace("\n", '', $value));
    }

    private function thenProperty_ShouldHave_Actions($propertyPos, $count) {
        $propertyPos--;
        $this->resource->thenThereShouldBe_Of($count, "entity/properties/item/$propertyPos/value/actions");
    }

    private function thenProperty_ShouldHaveAction_WithTheCaption($propertyPos, $actionPos, $name) {
        $propertyPos--;
        $actionPos--;
        $this->resource->then_ShouldBe("entity/properties/item/$propertyPos/value/actions/$actionPos/caption", $name);
    }

    private function thenProperty_ShouldHaveAction_WithTheLinkTarget($propertyPos, $actionPos, $target) {
        $propertyPos--;
        $actionPos--;
        $this->resource->then_ShouldBe("entity/properties/item/$propertyPos/value/actions/$actionPos/link/href", $target);
    }

} 