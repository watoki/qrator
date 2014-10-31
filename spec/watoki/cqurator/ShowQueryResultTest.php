<?php
namespace spec\watoki\cqurator;

use watoki\cqurator\web\QueryResource;
use watoki\curir\cookie\CookieStore;
use watoki\curir\cookie\SerializerRepository;
use watoki\scrut\Specification;

/**
 * Queries are executed and the object or array ob objects presented together with their actions.
 *
 * @property \spec\watoki\cqurator\fixtures\RegistryFixture registry <-
 * @property \spec\watoki\cqurator\fixtures\DispatcherFixture dispatcher <-
 * @property \spec\watoki\cqurator\fixtures\ClassFixture class <-
 * @property \watoki\scrut\ExceptionFixture try <-
 * @property \spec\watoki\cqurator\fixtures\ResourceFixture resource <-
 */
class ShowQueryResultTest extends Specification {

    public function background() {
        $this->class->givenTheClass('MyQuery');
    }

    function testNoActionsNorProperties() {
        $this->dispatcher->givenIAddedTheClosure_AsHandlerFor(function () {
            return new \StdClass;
        }, 'MyQuery');

        $this->whenIShowTheResultsOf('MyQuery');
        $this->thenTheNameShouldBe('std Class');
        $this->thenThereShouldBeNoProperties();
        $this->thenThereShouldBeNoQueries();
        $this->thenThereShouldBeNoCommands();
    }

    function testDisplayQueriesAndCommands() {
        $this->dispatcher->givenIAddedTheClosure_AsHandlerFor(function () {
            return new \DateTime();
        }, 'MyQuery');

        $this->registry->givenIRegisteredAnEntityRepresenterFor('DateTime');

        $this->registry->givenIAddedTheQuery_ToTheRepresenterOf('QueryOne', 'DateTime');
        $this->registry->givenIAddedTheQuery_ToTheRepresenterOf('QueryTwo', 'DateTime');
        $this->registry->givenIAddedTheCommand_ToTheRepresenterOf('CommandOne', 'DateTime');
        $this->registry->givenIAddedTheCommand_ToTheRepresenterOf('CommandTwo', 'DateTime');
        $this->registry->givenIAddedTheCommand_ToTheRepresenterOf('CommandThree', 'DateTime');

        $this->whenIShowTheResultsOf('MyQuery');
        $this->thenThereShouldBe_Queries(2);
        $this->thenThereShouldBe_Commands(3);

        $this->thenQuery_ShouldHaveTheName(1, 'Query One');
        $this->thenQuery_ShouldLinkTo(1, 'query?action=QueryOne');

        $this->thenCommand_ShouldHaveTheName(1, 'Command One');
        $this->thenCommand_ShouldLinkTo(1, 'command?action=CommandOne&do=post');
    }

    function testDisplayDynamicProperties() {
        $this->dispatcher->givenIAddedTheClosure_AsHandlerFor(function () {
            $object = new \StdClass();
            $object->propertyOne = 'valueOne';
            $object->propertyTwo = 'valueTwo';
            return $object;
        }, 'MyQuery');

        $this->whenIShowTheResultsOf('MyQuery');
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
            function myQuery() {
                return new MyClass();
            }
        ');
        $this->dispatcher->givenIAddedTheClass_AsHandlerFor('getters\MyHandler', 'MyQuery');

        $this->whenIShowTheResultsOf('MyQuery');
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
        }, 'MyQuery');

        $this->registry->givenIRegisteredAnEntityRepresenterFor('DateTime');
        $this->registry->givenIHaveTheTheRenderer_For(function (\DateTime $d) {
            return $d->format('Y-m-d H:i');
        }, 'DateTime');

        $this->whenIShowTheResultsOf('MyQuery');
        $this->thenThereShouldBe_Properties(1);
        $this->thenProperty_ShouldHaveTheName_AndValue(1, 'one', '2012-03-04 15:16');
    }

    function testDisplayCollection() {
        $this->dispatcher->givenIAddedTheClosure_AsHandlerFor(function () {
            return [new \DateTime(), new \DateTime(), new \DateTime()];
        }, 'MyQuery');

        $this->registry->givenIRegisteredAnEntityRepresenterFor('DateTime');
        $this->registry->givenIAddedTheQuery_ToTheRepresenterOf('QueryOne', 'DateTime');
        $this->registry->givenIAddedTheCommand_ToTheRepresenterOf('CommandOne', 'DateTime');

        $this->whenIShowTheResultsOf('MyQuery');
        $this->thenThereShouldBe_Entities(3);
        $this->thenEntity_ShouldHave_Properties(1, 6);
    }

    function testThrowExceptions() {
        $this->dispatcher->givenIAddedTheClosure_AsHandlerFor(function () {
            throw new \Exception('Something went wrong');
        }, 'MyQuery');

        $this->whenITryToShowTheResultsOf('MyQuery');
        $this->try->thenTheException_ShouldBeThrown('Something went wrong');
    }

    function testEntityActionsWithProperty() {
        $this->class->givenTheClass_WithTheBody('property\Entity', '
            public $id = "42";
        ');
        $this->class->givenTheClass_WithTheBody('property\MyHandler', '
            function myQuery() {
                return new Entity();
            }
        ');
        $this->dispatcher->givenIAddedTheClass_AsHandlerFor('property\MyHandler', 'MyQuery');

        $this->registry->givenIRegisteredAnEntityRepresenterFor('property\Entity');
        $this->registry->givenIAddedTheQuery_ToTheRepresenterOf('SomeQuery', 'property\Entity');
        $this->registry->givenIAddedTheCommand_ToTheRepresenterOf('SomeCommand', 'property\Entity');

        $this->whenIShowTheResultsOf('MyQuery');
        $this->thenQuery_ShouldLinkTo(1, 'query?action=SomeQuery&args[id]=42');
        $this->thenCommand_ShouldLinkTo(1, 'command?action=SomeCommand&do=post&args[id]=42');
    }

    function testEntityActionWithMethods() {
        $this->class->givenTheClass_WithTheBody('methods\Entity', '
            public function getId() { return "73"; }
        ');
        $this->class->givenTheClass_WithTheBody('methods\MyHandler', '
            function myQuery() {
                return new Entity();
            }
        ');
        $this->dispatcher->givenIAddedTheClass_AsHandlerFor('methods\MyHandler', 'MyQuery');

        $this->registry->givenIRegisteredAnEntityRepresenterFor('methods\Entity');
        $this->registry->givenIAddedTheQuery_ToTheRepresenterOf('SomeQuery', 'methods\Entity');
        $this->registry->givenIAddedTheCommand_ToTheRepresenterOf('SomeCommand', 'methods\Entity');

        $this->whenIShowTheResultsOf('MyQuery');
        $this->thenQuery_ShouldLinkTo(1, 'query?action=SomeQuery&args[id]=73');
        $this->thenCommand_ShouldLinkTo(1, 'command?action=SomeCommand&do=post&args[id]=73');
    }

    function testInvalidResult() {
        $this->dispatcher->givenIAddedTheClosure_AsHandlerFor(function () {
            return null;
        }, 'MyQuery');

        $this->whenITryToShowTheResultsOf('MyQuery');
        $this->try->thenTheException_ShouldBeThrown('Action had no displayable result: NULL');
    }

    function testShowArrayProperty() {
        $this->class->givenTheClass_WithTheBody('arrayProperty\SomeEntity', '
            function getArray() { return [
                new \DateTime("2001-01-01"),
                new \DateTime("2002-02-02")
            ]; }
        ');
        $this->class->givenTheClass_WithTheBody('arrayProperty\MyHandler', '
            function myQuery() {
                return new SomeEntity();
            }
        ');
        $this->dispatcher->givenIAddedTheClass_AsHandlerFor('arrayProperty\MyHandler', 'MyQuery');

        $this->registry->givenIRegisteredAnEntityRepresenterFor(\DateTime::class);
        $this->registry->givenIHaveTheTheRenderer_For(function (\DateTime $d) {
            return $d->format('Y-m-d H:i');
        }, \DateTime::class);

        $this->whenIShowTheResultsOf('MyQuery');
        $this->thenThereShouldBe_Properties(1);
        $this->thenProperty_ShouldHaveTheName(1, 'array');
        $this->thenProperty_ShouldHave_Value(1, 2);
        $this->thenProperty_ShouldHaveValue_WithTheCaption(1, 1, '2001-01-01 00:00');
    }

    function testShowQueriesAndCommandsForProperties() {
        $this->class->givenTheClass_WithTheBody('propertyActions\SomeEntity', '
            function __construct($one) { $this->one = $one; }
        ');
        $this->class->givenTheClass_WithTheBody('propertyActions\MyHandler', '
            function myQuery() {
                return new SomeEntity(new \DateTime());
            }
        ');
        $this->dispatcher->givenIAddedTheClass_AsHandlerFor('propertyActions\MyHandler', 'MyQuery');

        $this->registry->givenIRegisteredAnEntityRepresenterFor(\DateTime::class);
        $this->registry->givenIAddedTheQuery_ToTheRepresenterOf('SomeQuery', \DateTime::class);
        $this->registry->givenIAddedTheCommand_ToTheRepresenterOf('SomeCommand', \DateTime::class);

        $this->whenIShowTheResultsOf('MyQuery');
        $this->thenThereShouldBe_Properties(1);
        $this->thenProperty_ShouldHaveQuery_WithTheName(1, 1, 'Some Query');
        $this->thenProperty_ShouldHaveCommand_WithTheName(1, 1, 'Some Command');
    }

    function testPropertyQueries() {
        $this->class->givenTheClass_WithTheBody('propertyQueries\SomeEntity', '
            public $id = "someID";
            function __construct($other) { $this->other = $other; }
        ');
        $this->class->givenTheClass_WithTheBody('propertyQueries\OtherEntity', '
            public $id = "otherID";
        ');
        $this->class->givenTheClass_WithTheBody('propertyQueries\MyHandler', '
            function myQuery() {
                return new SomeEntity(new OtherEntity());
            }
        ');
        $this->dispatcher->givenIAddedTheClass_AsHandlerFor('propertyQueries\MyHandler', 'MyQuery');

        $this->class->givenTheClass('propertyQueries\PropertyQuery');
        $this->class->givenTheClass('propertyQueries\PropertyCommand');
        $this->registry->givenIRegisteredAnEntityRepresenterFor('propertyQueries\SomeEntity');

        $this->registry->givenIAddedAQuery_ForTheProperty_Of('propertyQueries\PropertyQuery', 'other', 'propertyQueries\SomeEntity');
        $this->registry->givenIAddedACommand_ForTheProperty_Of('propertyQueries\PropertyCommand', 'other', 'propertyQueries\SomeEntity');

        $this->whenIShowTheResultsOf('MyQuery');
        $this->thenThereShouldBe_Properties(2);
        $this->thenProperty_ShouldHaveQuery_WithTheName(1, 1, 'Property Query');
        $this->thenProperty_ShouldHaveQuery_WithTheLinkTarget(1, 1, 'query?action=propertyQueries%5CPropertyQuery&args[id]=someID&args[object]=otherID');
        $this->thenProperty_ShouldHaveCommand_WithTheName(1, 1, 'Property Command');
        $this->thenProperty_ShouldHaveCommand_WithTheLinkTarget(1, 1, 'command?action=propertyQueries%5CPropertyCommand&do=post&args[id]=someID&args[object]=otherID');
    }

    ###########################################################################################

    private function whenIShowTheResultsOf($query) {
        $cookies = new CookieStore(new SerializerRepository(), array());

        $this->resource->whenIDo_With(function (QueryResource $resource) use ($query) {
            return $resource->doGet($query, $this->resource->args);
        }, new QueryResource($this->factory, $this->dispatcher->dispatcher, $this->registry->registry, $cookies));
    }

    private function whenITryToShowTheResultsOf($query) {
        $this->try->tryTo(function () use ($query) {
            $this->whenIShowTheResultsOf($query);
        });
    }

    private function thenTheNameShouldBe($string) {
        $this->resource->then_ShouldBe('entity/name', $string);
    }

    private function thenThereShouldBe_Properties($int) {
        $this->resource->thenThereShouldBe_Of($int, 'entity/properties/property');
    }

    private function thenThereShouldBe_Queries($int) {
        $this->resource->thenThereShouldBe_Of($int, 'entity/queries/item');
    }

    private function thenThereShouldBe_Commands($int) {
        $this->resource->thenThereShouldBe_Of($int, 'entity/commands/item');
    }

    private function thenThereShouldBeNoProperties() {
        $this->resource->then_ShouldBe('entity/properties', null);
    }

    private function thenThereShouldBeNoQueries() {
        $this->resource->then_ShouldBe('entity/queries/isEmpty', true);
    }

    private function thenThereShouldBeNoCommands() {
        $this->resource->then_ShouldBe('entity/commands/isEmpty', true);
    }

    private function thenQuery_ShouldHaveTheName($int, $string) {
        $int--;
        $this->resource->then_ShouldBe("entity/queries/item/$int/name", $string);
    }

    private function thenQuery_ShouldLinkTo($int, $string) {
        $int--;
        $this->resource->then_ShouldBe("entity/queries/item/$int/link/href", $string);
    }

    private function thenCommand_ShouldHaveTheName($int, $string) {
        $int--;
        $this->resource->then_ShouldBe("entity/commands/item/$int/name", $string);
    }

    private function thenCommand_ShouldLinkTo($int, $string) {
        $int--;
        $this->resource->then_ShouldBe("entity/commands/item/$int/link/href", $string);
    }

    private function thenProperty_ShouldHaveTheName_AndValue($int, $name, $value) {
        $this->thenProperty_ShouldHaveTheName($int, $name);
        $int--;
        $this->resource->then_ShouldBe("entity/properties/property/$int/value/caption", $value);
    }

    private function thenProperty_ShouldHaveTheName($int, $name) {
        $int--;
        $this->resource->then_ShouldBe("entity/properties/property/$int/name", $name);
    }

    private function thenThereShouldBe_Entities($int) {
        $this->resource->thenThereShouldBe_Of($int, "entity");
    }

    private function thenEntity_ShouldHave_Properties($pos, $count) {
        $pos--;
        $this->resource->thenThereShouldBe_Of($count, "entity/$pos/properties/property");
    }

    private function thenProperty_ShouldHave_Value($pos, $count) {
        $pos--;
        $this->resource->thenThereShouldBe_Of($count, "entity/properties/property/$pos/value");
    }

    private function thenProperty_ShouldHaveValue_WithTheCaption($propertyPos, $valuePos, $caption) {
        $propertyPos--;
        $valuePos--;
        $this->resource->then_ShouldBe("entity/properties/property/$propertyPos/value/$valuePos/caption", $caption);
    }

    private function thenProperty_ShouldHaveQuery_WithTheName($propertyPos, $queryPos, $name) {
        $propertyPos--;
        $queryPos--;
        $this->resource->then_ShouldBe("entity/properties/property/$propertyPos/value/queries/$queryPos/name", $name);
    }

    private function thenProperty_ShouldHaveQuery_WithTheLinkTarget($propertyPos, $queryPos, $target) {
        $propertyPos--;
        $queryPos--;
        $this->resource->then_ShouldBe("entity/properties/property/$propertyPos/value/queries/$queryPos/link/href", $target);
    }

    private function thenProperty_ShouldHaveCommand_WithTheName($propertyPos, $queryPos, $name) {
        $propertyPos--;
        $queryPos--;
        $this->resource->then_ShouldBe("entity/properties/property/$propertyPos/value/commands/$queryPos/name", $name);
    }

    private function thenProperty_ShouldHaveCommand_WithTheLinkTarget($propertyPos, $queryPos, $target) {
        $propertyPos--;
        $queryPos--;
        $this->resource->then_ShouldBe("entity/properties/property/$propertyPos/value/commands/$queryPos/link/href", $target);
    }

} 