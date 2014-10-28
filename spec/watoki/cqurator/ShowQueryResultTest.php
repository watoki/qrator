<?php
namespace spec\watoki\cqurator;

use watoki\cqurator\web\QueryResource;
use watoki\scrut\Specification;

/**
 * @property \spec\watoki\cqurator\fixtures\RegistryFixture registry <-
 * @property \spec\watoki\cqurator\fixtures\DispatcherFixture dispatcher <-
 * @property \spec\watoki\cqurator\fixtures\ClassFixture class <-
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

    function testDisplayProperties() {
        $this->markTestIncomplete();
    }

    function testRenderObjectProperties() {
        $this->markTestIncomplete();
    }

    function testDisplayCollection() {
        $this->markTestIncomplete();
    }

    ###########################################################################################

    private $returned;

    private function whenIShowTheResultsOf($query) {
        $resource = new QueryResource($this->dispatcher->dispatcher, $this->registry->registry);
        $this->returned = $resource->doGet($query);
    }

    private function thenThereShouldBe_Properties($int) {
        $this->assertCount($int, $this->returned['properties']['property']);
    }

    private function thenThereShouldBe_Queries($int) {
        $this->assertCount($int, $this->returned['queries']['action']);
    }

    private function thenThereShouldBe_Commands($int) {
        $this->assertCount($int, $this->returned['commands']['action']);
    }

    private function thenThereShouldBeNoProperties() {
        $this->assertNull($this->returned['properties']);
    }

    private function thenThereShouldBeNoQueries() {
        $this->assertNull($this->returned['queries']);
    }

    private function thenThereShouldBeNoCommands() {
        $this->assertNull($this->returned['commands']);
    }

    private function thenQuery_ShouldHaveTheName($int, $string) {
        $this->assertEquals($string, $this->returned['queries']['action'][$int - 1]['name']);
    }

    private function thenQuery_ShouldLinkTo($int, $string) {
        $this->assertEquals($string, $this->returned['queries']['action'][$int - 1]['link']['href']);
    }

    private function thenCommand_ShouldHaveTheName($int, $string) {
        $this->assertEquals($string, $this->returned['commands']['action'][$int - 1]['name']);
    }

    private function thenCommand_ShouldLinkTo($int, $string) {
        $this->assertEquals($string, $this->returned['commands']['action'][$int - 1]['link']['href']);
    }

} 