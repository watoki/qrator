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
        $this->thenTheNameShouldBe('stdClass');
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

        $this->thenQuery_ShouldHaveTheName(1, 'QueryOne');
        $this->thenQuery_ShouldLinkTo(1, 'query?action=QueryOne');

        $this->thenCommand_ShouldHaveTheName(1, 'CommandOne');
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
        $this->resource->thenThereShouldBe_Of($int, 'entity/queries/action');
    }

    private function thenThereShouldBe_Commands($int) {
        $this->resource->thenThereShouldBe_Of($int, 'entity/commands/action');
    }

    private function thenThereShouldBeNoProperties() {
        $this->resource->then_ShouldBe('entity/properties', null);
    }

    private function thenThereShouldBeNoQueries() {
        $this->resource->then_ShouldBe('entity/queries', null);
    }

    private function thenThereShouldBeNoCommands() {
        $this->resource->then_ShouldBe('entity/commands', null);
    }

    private function thenQuery_ShouldHaveTheName($int, $string) {
        $int--;
        $this->resource->then_ShouldBe("entity/queries/action/$int/name", $string);
    }

    private function thenQuery_ShouldLinkTo($int, $string) {
        $int--;
        $this->resource->then_ShouldBe("entity/queries/action/$int/link/href", $string);
    }

    private function thenCommand_ShouldHaveTheName($int, $string) {
        $int--;
        $this->resource->then_ShouldBe("entity/commands/action/$int/name", $string);
    }

    private function thenCommand_ShouldLinkTo($int, $string) {
        $int--;
        $this->resource->then_ShouldBe("entity/commands/action/$int/link/href", $string);
    }

    private function thenProperty_ShouldHaveTheName_AndValue($int, $name, $value) {
        $int--;
        $this->resource->then_ShouldBe("entity/properties/property/$int/name", $name);
        $this->resource->then_ShouldBe("entity/properties/property/$int/value", $value);
    }

    private function thenThereShouldBe_Entities($int) {
        $this->resource->thenThereShouldBe_Of($int, "entity");
    }

    private function thenEntity_ShouldHave_Properties($pos, $count) {
        $pos--;
        $this->resource->thenThereShouldBe_Of($count, "entity/$pos/properties/property");
    }

} 