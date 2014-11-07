<?php
namespace spec\watoki\qrator;

use watoki\collections\Liste;
use watoki\curir\cookie\CookieStore;
use watoki\curir\cookie\SerializerRepository;
use watoki\factory\exception\InjectionException;
use watoki\qrator\web\ExecuteResource;
use watoki\scrut\Specification;

/**
 * Actions are executed and the object or array ob objects presented together with their actions.
 *
 * @property \spec\watoki\qrator\fixtures\RegistryFixture registry <-
 * @property \spec\watoki\qrator\fixtures\DispatcherFixture dispatcher <-
 * @property \spec\watoki\qrator\fixtures\ClassFixture class <-
 * @property \watoki\scrut\ExceptionFixture try <-
 * @property \spec\watoki\qrator\fixtures\ResourceFixture resource <-
 */
class ShowActionResultTest extends Specification {

    public function background() {
        $this->class->givenTheClass('MyAction');
    }

    function testNoActionsNorProperties() {
        $this->dispatcher->givenIAddedTheClosure_AsHandlerFor(function () {
            return new \StdClass;
        }, 'MyAction');

        $this->whenIShowTheResultsOf('MyAction');
        $this->thenTheNameShouldBe('std Class');
        $this->thenThereShouldBeNoProperties();
        $this->thenThereShouldBeNoActions();
    }

    function testDisplayActions() {
        $this->dispatcher->givenIAddedTheClosure_AsHandlerFor(function () {
            return new \DateTime();
        }, 'MyAction');

        $this->registry->givenIRegisteredAnEntityRepresenterFor('DateTime');

        $this->registry->givenIAddedTheAction_ToTheRepresenterOf('ActionOne', 'DateTime');
        $this->registry->givenIAddedTheAction_ToTheRepresenterOf('ActionTwo', 'DateTime');
        $this->registry->givenIAddedTheAction_ToTheRepresenterOf('AnotherOne', 'DateTime');
        $this->registry->givenIAddedTheAction_ToTheRepresenterOf('AnotherTwo', 'DateTime');
        $this->registry->givenIAddedTheAction_ToTheRepresenterOf('AnotherThree', 'DateTime');

        $this->whenIShowTheResultsOf('MyAction');
        $this->thenThereShouldBe_Actions(5);

        $this->thenAction_ShouldHaveTheName(1, 'Action One');
        $this->thenAction_ShouldLinkTo(1, 'execute?action=ActionOne');
        $this->thenAction_ShouldHaveTheName(3, 'Another One');
        $this->thenAction_ShouldLinkTo(3, 'execute?action=AnotherOne');
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
        $this->thenProperty_ShouldHaveTheName_AndValue(1, 'propertyOne', 'valueOne');
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
        $this->thenProperty_ShouldHaveTheName_AndValue(1, 'zero', 'null');
        $this->thenProperty_ShouldHaveTheName_AndValue(2, 'one', 'uno');
        $this->thenProperty_ShouldHaveTheName_AndValue(3, 'two', 'dos');
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
        $this->thenProperty_ShouldHaveTheName_AndValue(1, 'one', '2012-03-04 15:16');

        $this->thenThe_ShouldNotBeShown('table');
    }

    function testDisplayCollection() {
        $this->dispatcher->givenIAddedTheClosure_AsHandlerFor(function () {
            return [new \DateTime(), new \DateTime(), new \DateTime()];
        }, 'MyAction');

        $this->registry->givenIRegisteredAnEntityRepresenterFor('DateTime');
        $this->registry->givenIAddedTheAction_ToTheRepresenterOf('ActionOne', 'DateTime');
        $this->registry->givenIAddedTheAction_ToTheRepresenterOf('AnotherOne', 'DateTime');

        $this->whenIShowTheResultsOf('MyAction');
        $this->thenThereShouldBe_Entities(3);
        $this->thenEntity_ShouldHave_Properties(1, 3);
        $this->thenEntity_ShouldHave_Actions(1, 2);

        $this->thenThe_ShouldNotBeShown('list');
    }

    function testDisplayCollectionObject() {
        $this->dispatcher->givenIAddedTheClosure_AsHandlerFor(function () {
            return new Liste([new \StdClass, new \StdClass]);
        }, 'MyAction');

        $this->whenIShowTheResultsOf('MyAction');
        $this->thenThereShouldBe_Entities(2);
    }

    function testThrowExceptions() {
        $this->dispatcher->givenIAddedTheClosure_AsHandlerFor(function () {
            throw new \Exception('Something went wrong');
        }, 'MyAction');

        $this->whenITryToShowTheResultsOf('MyAction');
        $this->try->thenTheException_ShouldBeThrown('Something went wrong');
    }

    function testEntityActionsWithProperty() {
        $this->class->givenTheClass_WithTheBody('property\Entity', '
            public $id = "42";
        ');
        $this->class->givenTheClass_WithTheBody('property\MyHandler', '
            function myAction() {
                return new Entity();
            }
        ');
        $this->dispatcher->givenIAddedTheClass_AsHandlerFor('property\MyHandler', 'MyAction');

        $this->registry->givenIRegisteredAnEntityRepresenterFor('property\Entity');
        $this->registry->givenIAddedTheAction_ToTheRepresenterOf('SomeAction', 'property\Entity');
        $this->registry->givenIAddedTheAction_ToTheRepresenterOf('SomeAnother', 'property\Entity');

        $this->whenIShowTheResultsOf('MyAction');
        $this->thenAction_ShouldLinkTo(1, 'execute?action=SomeAction&args[id]=42');
        $this->thenAction_ShouldLinkTo(2, 'execute?action=SomeAnother&args[id]=42');
    }

    function testEntityActionWithMethods() {
        $this->class->givenTheClass_WithTheBody('methods\Entity', '
            public function getId() { return "73"; }
        ');
        $this->class->givenTheClass_WithTheBody('methods\MyHandler', '
            function myAction() {
                return new Entity();
            }
        ');
        $this->dispatcher->givenIAddedTheClass_AsHandlerFor('methods\MyHandler', 'MyAction');

        $this->registry->givenIRegisteredAnEntityRepresenterFor('methods\Entity');
        $this->registry->givenIAddedTheAction_ToTheRepresenterOf('SomeAction', 'methods\Entity');
        $this->registry->givenIAddedTheAction_ToTheRepresenterOf('SomeAnother', 'methods\Entity');

        $this->whenIShowTheResultsOf('MyAction');
        $this->thenAction_ShouldLinkTo(1, 'execute?action=SomeAction&args[id]=73');
        $this->thenAction_ShouldLinkTo(2, 'execute?action=SomeAnother&args[id]=73');
    }

    function testNoResult() {
        $this->dispatcher->givenIAddedTheClosure_AsHandlerFor(function () {
            return null;
        }, 'MyAction');

        $this->whenIShowTheResultsOf('MyAction');
        $this->thenAnAlertShouldSay("Action executed successfully. Empty result.");
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
        $this->class->givenTheClass_WithTheBody('properties\SomeEntity', '
            function __construct($one) { $this->one = $one; }
        ');
        $this->class->givenTheClass_WithTheBody('properties\MyHandler', '
            function myAction() {
                return new SomeEntity(new \DateTime());
            }
        ');
        $this->dispatcher->givenIAddedTheClass_AsHandlerFor('properties\MyHandler', 'MyAction');

        $this->registry->givenIRegisteredAnEntityRepresenterFor(\DateTime::class);
        $this->registry->givenIAddedTheAction_ToTheRepresenterOf('SomeAction', \DateTime::class);
        $this->registry->givenIAddedTheAction_ToTheRepresenterOf('SomeAnother', \DateTime::class);

        $this->whenIShowTheResultsOf('MyAction');
        $this->thenThereShouldBe_Properties(1);
        $this->thenProperty_ShouldHave_Actions(1, 2);
        $this->thenProperty_ShouldHaveAction_WithTheName(1, 1, 'Some Action');
        $this->thenProperty_ShouldHaveAction_WithTheName(1, 2, 'Some Another');
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
        $this->thenProperty_ShouldHaveAction_WithTheName(1, 1, 'Property Action');
        $this->thenProperty_ShouldHaveAction_WithTheLinkTarget(1, 1, 'execute?action=propertyActions%5CPropertyAction&args[id]=someID&args[object]=otherID');
        $this->thenProperty_ShouldHaveAction_WithTheName(1, 2, 'Property Another');
        $this->thenProperty_ShouldHaveAction_WithTheLinkTarget(1, 2, 'execute?action=propertyActions%5CPropertyAnother&args[id]=someID&args[object]=otherID');
    }

    function testEdgeCaseDoNotRedirectOnInjectionExceptionDuringExecution() {
        $this->dispatcher->givenIAddedTheClosure_AsHandlerFor(function () {
            throw new InjectionException('Something went wrong');
        }, 'MyAction');

        $this->whenITryToShowTheResultsOf('MyAction');
        $this->try->thenTheException_ShouldBeThrown('Something went wrong');
    }

    ###########################################################################################

    private function whenIShowTheResultsOf($action) {
        $cookies = new CookieStore(new SerializerRepository(), array());

        $this->resource->whenIDo_With(function (ExecuteResource $resource) use ($action) {
            return $resource->doGet($action, $this->resource->args);
        }, new ExecuteResource($this->factory, $this->registry->registry, $cookies));
    }

    private function whenITryToShowTheResultsOf($action) {
        $this->try->tryTo(function () use ($action) {
            $this->whenIShowTheResultsOf($action);
        });
    }

    private function thenTheNameShouldBe($string) {
        $this->resource->then_ShouldBe('entity/0/name', $string);
    }

    private function thenThereShouldBe_Properties($int) {
        $this->resource->thenThereShouldBe_Of($int, 'entity/0/properties/item');
    }

    private function thenThereShouldBe_Actions($int) {
        $this->resource->thenThereShouldBe_Of($int, 'entity/0/actions/item');
    }

    private function thenThereShouldBeNoProperties() {
        $this->resource->then_ShouldBe('entity/0/properties/isEmpty', true);
    }

    private function thenThereShouldBeNoActions() {
        $this->resource->then_ShouldBe('entity/0/actions/isEmpty', true);
    }

    private function thenAction_ShouldHaveTheName($int, $string) {
        $int--;
        $this->resource->then_ShouldBe("entity/0/actions/item/$int/name", $string);
    }

    private function thenAction_ShouldLinkTo($int, $string) {
        $int--;
        $this->resource->then_ShouldBe("entity/0/actions/item/$int/link/href", $string);
    }

    private function thenProperty_ShouldHaveTheName_AndValue($int, $name, $value) {
        $this->thenProperty_ShouldHaveTheName($int, $name);
        $int--;
        $this->resource->then_ShouldBe("entity/0/properties/item/$int/value/caption", $value);
    }

    private function thenProperty_ShouldHaveTheName($int, $name) {
        $int--;
        $this->resource->then_ShouldBe("entity/0/properties/item/$int/name", $name);
    }

    private function thenThereShouldBe_Entities($int) {
        $this->resource->thenThereShouldBe_Of($int, "entity");
    }

    private function thenEntity_ShouldHave_Properties($pos, $count) {
        $pos--;
        $this->resource->thenThereShouldBe_Of($count, "entity/$pos/properties/item");
    }

    private function thenEntity_ShouldHave_Actions($pos, $count) {
        $pos--;
        $this->resource->thenThereShouldBe_Of($count, "entity/$pos/actions/item");
    }

    private function thenProperty_ShouldHave_Value($pos, $count) {
        $pos--;
        $this->resource->thenThereShouldBe_Of($count, "entity/0/properties/item/$pos/value");
    }

    private function thenProperty_ShouldHaveValue_WithTheCaption($propertyPos, $valuePos, $caption) {
        $propertyPos--;
        $valuePos--;
        $value = $this->resource->get("entity/0/properties/item/$propertyPos/value/$valuePos/caption");
        $this->assertEquals($caption, str_replace("\n", '', $value));
    }

    private function thenProperty_ShouldHave_Actions($propertyPos, $count) {
        $propertyPos--;
        $this->resource->thenThereShouldBe_Of($count, "entity/0/properties/item/$propertyPos/value/actions");
    }

    private function thenProperty_ShouldHaveAction_WithTheName($propertyPos, $actionPos, $name) {
        $propertyPos--;
        $actionPos--;
        $this->resource->then_ShouldBe("entity/0/properties/item/$propertyPos/value/actions/$actionPos/name", $name);
    }

    private function thenProperty_ShouldHaveAction_WithTheLinkTarget($propertyPos, $actionPos, $target) {
        $propertyPos--;
        $actionPos--;
        $this->resource->then_ShouldBe("entity/0/properties/item/$propertyPos/value/actions/$actionPos/link/href", $target);
    }

    private function thenAnAlertShouldSay($string) {
        $this->resource->then_ShouldBe('alert', $string);
    }

    private function thenThe_ShouldNotBeShown($element) {
        $this->resource->then_ShouldExist("$element/class");
    }

} 