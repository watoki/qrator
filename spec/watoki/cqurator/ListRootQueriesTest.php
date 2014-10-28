<?php
namespace spec\watoki\cqurator;

use watoki\cqurator\web\IndexResource;
use watoki\cqurator\representer\GenericRepresenter;
use watoki\cqurator\RepresenterRegistry;
use watoki\scrut\Specification;

/**
 * The Representer of class `null` is the *root Representer*. It's queries are listed if no Action is given.
 *
 * @property \spec\watoki\cqurator\fixtures\RegistryFixture registry <-
 */
class ListRootQueriesTest extends Specification {

    function testNoQueriesRegistered() {
        $this->whenIOpenTheIndexResource();
        $this->thenThereShouldBeNoQueries();
    }

    function testTwoQueriesRegistered() {
        $this->registry->givenIRegisteredARepresenterFor(null);
        $this->registry->givenIAddedTheQuery_ToTheRepresenterOf('some\Query', null);
        $this->registry->givenIAddedTheQuery_ToTheRepresenterOf('other\Query', null);

        $this->whenIOpenTheIndexResource();
        $this->thenThereShouldBe_Queries(2);
        $this->thenQuery_ShouldBe(1, 'some\Query');
        $this->thenQuery_ShouldBe(2, 'other\Query');
        $this->thenQuery_ShouldLinkTo(1, '?action=some\Query');
        $this->thenQuery_ShouldLinkTo(2, '?action=other\Query');
    }

    ####################################################################################################

    private $returned;

    private function whenIOpenTheIndexResource() {
        $resource = new IndexResource($this->factory, $this->registry->registry);
        $this->returned = $resource->doGet();
    }

    private function thenThereShouldBeNoQueries() {
        $this->thenThereShouldBe_Queries(0);
    }

    private function thenThereShouldBe_Queries($int) {
        $this->assertCount($int, $this->returned['query']);
    }

    private function thenQuery_ShouldBe($pos, $string) {
        $this->assertEquals($string, $this->returned['query'][$pos - 1]['name']);
    }

    private function thenQuery_ShouldLinkTo($pos, $string) {
        $this->assertEquals($string, $this->returned['query'][$pos - 1]['link']['href']);
    }

} 