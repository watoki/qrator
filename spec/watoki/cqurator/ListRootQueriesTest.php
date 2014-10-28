<?php
namespace spec\watoki\cqurator;

use watoki\cqurator\web\IndexResource;
use watoki\cqurator\representer\GenericRepresenter;
use watoki\cqurator\RepresenterRegistry;
use watoki\scrut\Specification;

class ListRootQueriesTest extends Specification {

    function testNoQueriesRegistered() {
        $this->whenIOpenTheIndexResource();
        $this->thenThereShouldBeNoQueries();
    }

    function testTwoQueriesRegistered() {
        $this->givenIRegisteredARepresenterFor(null);
        $this->givenIAddedTheQuery_ToTheRepresenterOf('some\Query', null);
        $this->givenIAddedTheQuery_ToTheRepresenterOf('other\Query', null);

        $this->whenIOpenTheIndexResource();
        $this->thenThereShouldBe_Queries(2);
        $this->thenQuery_ShouldBe(1, 'some\Query');
        $this->thenQuery_ShouldBe(2, 'other\Query');
        $this->thenQuery_ShouldLinkTo(1, '?action=some\Query');
        $this->thenQuery_ShouldLinkTo(2, '?action=other\Query');
    }

    ####################################################################################################

    private $returned;

    /** @var RepresenterRegistry */
    private $registry;

    /** @var GenericRepresenter[] */
    private $representers = array();

    protected function setUp() {
        parent::setUp();
        $this->registry = new RepresenterRegistry($this->factory);
    }

    private function givenIRegisteredARepresenterFor($class) {
        $this->representers[$class] = new GenericRepresenter($this->factory);
        $this->registry->register($class, $this->representers[$class]);
    }

    private function givenIAddedTheQuery_ToTheRepresenterOf($query, $class) {
        $this->representers[$class]->addQuery($query);
    }

    private function whenIOpenTheIndexResource() {
        $resource = new IndexResource($this->factory, $this->registry);
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