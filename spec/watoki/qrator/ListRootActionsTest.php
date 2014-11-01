<?php
namespace spec\watoki\qrator;

use watoki\qrator\web\IndexResource;
use watoki\scrut\Specification;

/**
 * The Representer of class `null` is the *root Representer*. It's actions are listed by IndexResource.
 *
 * @property \spec\watoki\qrator\fixtures\RegistryFixture registry <-
 * @property \spec\watoki\qrator\fixtures\ResourceFixture resource <-
 */
class ListRootActionsTest extends Specification {

    function testNoActionsRegistered() {
        $this->whenIOpenTheIndexResource();
        $this->thenThereShouldBeNoActions();
    }

    function testTwoActionsRegistered() {
        $this->registry->givenIRegisteredAnEntityRepresenterFor(null);
        $this->registry->givenIAddedTheAction_ToTheRepresenterOf('some\ActionOne', null);
        $this->registry->givenIAddedTheAction_ToTheRepresenterOf('other\ActionTwo', null);

        $this->whenIOpenTheIndexResource();
        $this->thenThereShouldBe_Actions(2);
        $this->thenAction_ShouldBe(1, 'Action One');
        $this->thenAction_ShouldBe(2, 'Action Two');
        $this->thenAction_ShouldLinkTo(1, 'execute?action=some\ActionOne');
        $this->thenAction_ShouldLinkTo(2, 'execute?action=other\ActionTwo');
    }

    ####################################################################################################

    private function whenIOpenTheIndexResource() {
        $this->resource->whenIDo_With(function (IndexResource $resource) {
            return $resource->doGet();
        }, new IndexResource($this->factory, $this->registry->registry));
    }

    private function thenThereShouldBeNoActions() {
        $this->thenThereShouldBe_Actions(0);
    }

    private function thenThereShouldBe_Actions($int) {
        $this->resource->thenThereShouldBe_Of($int, 'action');
    }

    private function thenAction_ShouldBe($pos, $string) {
        $pos--;
        $this->resource->then_ShouldBe("action/$pos/name", $string);
    }

    private function thenAction_ShouldLinkTo($pos, $string) {
        $pos--;
        $this->resource->then_ShouldBe("action/$pos/link/href", $string);
    }

} 