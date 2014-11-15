<?php
namespace spec\watoki\qrator;

use watoki\curir\cookie\CookieStore;
use watoki\qrator\RootAction;
use watoki\qrator\web\ExecuteResource;
use watoki\qrator\web\IndexResource;
use watoki\scrut\Specification;

/**
 * The Representer of class `null` is the *root Representer*. It's actions are listed by IndexResource.
 *
 * @property \spec\watoki\qrator\fixtures\RegistryFixture registry <-
 * @property \spec\watoki\qrator\fixtures\ResourceFixture resource <-
 */
class ListRootActionsTest extends Specification {

    function testForwardToRootAction() {
        $this->whenIOpenTheIndexResource();
        $this->resource->thenIShouldBeRedirectedTo('execute?action=watoki%5Cqrator%5CRootAction');
    }

    function testRootActionReturnsItself() {
        $this->whenIExecute(RootAction::class);
        $this->thenItShouldShouldTheEntity('Qrator');
    }

    ####################################################################################################

    private function whenIOpenTheIndexResource() {
        $this->resource->whenIDo_With(function (IndexResource $resource) {
            return $resource->doGet();
        }, IndexResource::class);
    }

    private function whenIExecute($class) {
        $this->resource->whenIDo_With(function (ExecuteResource $resource) use ($class) {
            return $resource->doGet($class);
        }, ExecuteResource::class);
    }

    private function thenItShouldShouldTheEntity($string) {
        $this->resource->then_ShouldBe('entity/0/name', $string);
    }

} 