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

    function testNoActionsNorProperties() {
        $this->class->givenTheClass('nothing\MyQuery');
        $this->dispatcher->givenIAddedTheClosure_AsHandlerFor(function () {
            return new \StdClass;
        }, 'nothing\MyQuery');

        $this->whenIShowTheResultsOf('nothing\MyQuery');
        $this->thenThereShouldBe_Properties(0);
        $this->thenThereShouldBe_Queries(0);
        $this->thenThereShouldBe_Commands(0);
    }

    function testDisplayQueriesAndCommands() {
        $this->markTestIncomplete();
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
        $resource = new QueryResource($this->dispatcher->dispatcher);
        $this->returned = $resource->doGet($query);
    }

    private function thenThereShouldBe_Queries($int) {
        $this->assertCount($int, $this->returned['queries']);
    }

    private function thenThereShouldBe_Properties($int) {
        $this->assertCount($int, $this->returned['properties']);
    }

    private function thenThereShouldBe_Commands($int) {
        $this->assertCount($int, $this->returned['commands']);
    }

} 