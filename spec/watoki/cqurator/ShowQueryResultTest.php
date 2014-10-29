<?php
namespace spec\watoki\cqurator;

use watoki\cqurator\web\QueryResource;
use watoki\deli\Path;
use watoki\deli\Request;
use watoki\scrut\Specification;

/**
 * Queries are executed and the object or array ob objects presented together with their actions.
 *
 * @property \spec\watoki\cqurator\fixtures\RegistryFixture registry <-
 * @property \spec\watoki\cqurator\fixtures\DispatcherFixture dispatcher <-
 * @property \spec\watoki\cqurator\fixtures\ClassFixture class <-
 * @property \watoki\scrut\ExceptionFixture try <-
 */
class ShowQueryResultTest extends Specification {

    protected function background() {
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

        $this->registry->givenIRegisteredARepresenterFor('DateTime');

        $this->registry->givenIAddedTheQuery_ToTheRepresenterOf('QueryOne', 'DateTime');
        $this->registry->givenIAddedTheQuery_ToTheRepresenterOf('QueryTwo', 'DateTime');
        $this->registry->givenIAddedTheCommand_ToTheRepresenterOf('CommandOne', 'DateTime');
        $this->registry->givenIAddedTheCommand_ToTheRepresenterOf('CommandTwo', 'DateTime');
        $this->registry->givenIAddedTheCommand_ToTheRepresenterOf('CommandThree', 'DateTime');

        $this->whenIShowTheResultsOf('MyQuery');
        $this->thenThereShouldBe_Queries(2);
        $this->thenThereShouldBe_Commands(3);

        $this->thenQuery_ShouldHaveTheName(1, 'QueryOne');
        $this->thenQuery_ShouldLinkTo(1, '?action=QueryOne');

        $this->thenCommand_ShouldHaveTheName(1, 'CommandOne');
        $this->thenCommand_ShouldLinkTo(1, '?action=CommandOne&do=post');
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
            public $zero = "zero";
            function getOne() { return "one";}
            function getTwo() { return "two"; }
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
        $this->thenProperty_ShouldHaveTheName_AndValue(2, 'One', 'one');
    }

    function testRenderObjectProperties() {
        $this->dispatcher->givenIAddedTheClosure_AsHandlerFor(function () {
            $object = new \StdClass;
            $object->one = new \DateTime('2012-03-04 15:16');
            return $object;
        }, 'MyQuery');

        $this->registry->givenIRegisteredARepresenterFor('DateTime');
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

        $this->registry->givenIRegisteredARepresenterFor('DateTime');
        $this->registry->givenIAddedTheQuery_ToTheRepresenterOf('QueryOne', 'DateTime');
        $this->registry->givenIAddedTheCommand_ToTheRepresenterOf('CommandOne', 'DateTime');

        $this->whenIShowTheResultsOf('MyQuery');
        $this->thenThereShouldBe_Entities(3);
        $this->thenEntity_ShouldHave_Properties(1, 7);
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

        $this->registry->givenIRegisteredARepresenterFor('property\Entity');
        $this->registry->givenIAddedTheQuery_ToTheRepresenterOf('SomeQuery', 'property\Entity');
        $this->registry->givenIAddedTheCommand_ToTheRepresenterOf('SomeCommand', 'property\Entity');

        $this->whenIShowTheResultsOf('MyQuery');
        $this->thenQuery_ShouldLinkTo(1, '?action=SomeQuery&id=42');
        $this->thenCommand_ShouldLinkTo(1, '?action=SomeCommand&do=post&id=42');
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

        $this->registry->givenIRegisteredARepresenterFor('methods\Entity');
        $this->registry->givenIAddedTheQuery_ToTheRepresenterOf('SomeQuery', 'methods\Entity');
        $this->registry->givenIAddedTheCommand_ToTheRepresenterOf('SomeCommand', 'methods\Entity');

        $this->whenIShowTheResultsOf('MyQuery');
        $this->thenQuery_ShouldLinkTo(1, '?action=SomeQuery&id=73');
        $this->thenCommand_ShouldLinkTo(1, '?action=SomeCommand&do=post&id=73');
    }

    function testInvalidResult() {
        $this->dispatcher->givenIAddedTheClosure_AsHandlerFor(function () {
            return null;
        }, 'MyQuery');

        $this->whenITryToShowTheResultsOf('MyQuery');
        $this->try->thenTheException_ShouldBeThrown('Action had no displayable result: NULL');
    }

    ###########################################################################################

    private $returned;

    private function whenIShowTheResultsOf($query) {
        $request = new Request(new Path(), new Path());
        $resource = new QueryResource($this->dispatcher->dispatcher, $this->registry->registry, $this->factory);
        $this->returned = $resource->doGet($request, $query);
    }

    private function whenITryToShowTheResultsOf($query) {
        $this->try->tryTo(function () use ($query) {
            $this->whenIShowTheResultsOf($query);
        });
    }

    private function thenTheNameShouldBe($string) {
        $this->assertEquals($string, $this->returned['entity']['name']);
    }

    private function thenThereShouldBe_Properties($int) {
        $this->assertCount($int, $this->returned['entity']['properties']['property']);
    }

    private function thenThereShouldBe_Queries($int) {
        $this->assertCount($int, $this->returned['entity']['queries']['action']);
    }

    private function thenThereShouldBe_Commands($int) {
        $this->assertCount($int, $this->returned['entity']['commands']['action']);
    }

    private function thenThereShouldBeNoProperties() {
        $this->assertNull($this->returned['entity']['properties']);
    }

    private function thenThereShouldBeNoQueries() {
        $this->assertNull($this->returned['entity']['queries']);
    }

    private function thenThereShouldBeNoCommands() {
        $this->assertNull($this->returned['entity']['commands']);
    }

    private function thenQuery_ShouldHaveTheName($int, $string) {
        $this->assertEquals($string, $this->returned['entity']['queries']['action'][$int - 1]['name']);
    }

    private function thenQuery_ShouldLinkTo($int, $string) {
        $this->assertEquals($string, $this->returned['entity']['queries']['action'][$int - 1]['link']['href']);
    }

    private function thenCommand_ShouldHaveTheName($int, $string) {
        $this->assertEquals($string, $this->returned['entity']['commands']['action'][$int - 1]['name']);
    }

    private function thenCommand_ShouldLinkTo($int, $string) {
        $this->assertEquals($string, $this->returned['entity']['commands']['action'][$int - 1]['link']['href']);
    }

    private function thenProperty_ShouldHaveTheName_AndValue($int, $name, $value) {
        $this->assertEquals($name, $this->returned['entity']['properties']['property'][$int - 1]['name']);
        $this->assertEquals($value, $this->returned['entity']['properties']['property'][$int - 1]['value']);
    }

    private function thenThereShouldBe_Entities($int) {
        $this->assertCount($int, $this->returned['entity']);
    }

    private function thenEntity_ShouldHave_Properties($pos, $count) {
        $this->assertCount($count, $this->returned['entity'][$pos - 1]['properties']['property']);
    }

} 