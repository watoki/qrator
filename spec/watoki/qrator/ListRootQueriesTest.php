<?php
namespace spec\watoki\qrator;

use watoki\qrator\web\IndexResource;
use watoki\scrut\Specification;

/**
 * The Representer of class `null` is the *root Representer*. It's queries are listed if no Action is given.
 * @property \spec\watoki\qrator\fixtures\RegistryFixture registry <-
 * @property \spec\watoki\qrator\fixtures\ResourceFixture resource <-
 */
class ListRootQueriesTest extends Specification {

    function testNoQueriesRegistered() {
        $this->whenIOpenTheIndexResource();
        $this->thenThereShouldBeNoQueries();
    }

    function testTwoQueriesRegistered() {
        $this->registry->givenIRegisteredAnEntityRepresenterFor(null);
        $this->registry->givenIAddedTheQuery_ToTheRepresenterOf('some\QueryOne', null);
        $this->registry->givenIAddedTheQuery_ToTheRepresenterOf('other\QueryTwo', null);

        $this->whenIOpenTheIndexResource();
        $this->thenThereShouldBe_Queries(2);
        $this->thenQuery_ShouldBe(1, 'Query One');
        $this->thenQuery_ShouldBe(2, 'Query Two');
        $this->thenQuery_ShouldLinkTo(1, 'query?action=some\QueryOne');
        $this->thenQuery_ShouldLinkTo(2, 'query?action=other\QueryTwo');
    }

    ####################################################################################################

    private function whenIOpenTheIndexResource() {
        $this->resource->whenIDo_With(function (IndexResource $resource) {
            return $resource->doGet();
        }, new IndexResource($this->factory, $this->registry->registry));
    }

    private function thenThereShouldBeNoQueries() {
        $this->thenThereShouldBe_Queries(0);
    }

    private function thenThereShouldBe_Queries($int) {
        $this->resource->thenThereShouldBe_Of($int, 'query');
    }

    private function thenQuery_ShouldBe($pos, $string) {
        $pos--;
        $this->resource->then_ShouldBe("query/$pos/name", $string);
    }

    private function thenQuery_ShouldLinkTo($pos, $string) {
        $pos--;
        $this->resource->then_ShouldBe("query/$pos/link/href", $string);
    }

} 