<?php
namespace spec\watoki\cqurator;
use watoki\cqurator\web\QueryResource;
use watoki\scrut\Specification;

/**
 * A Query gets published over the ActionDispatcher and the resulting objects together with their Actions displayed.
 *
 * @property \spec\watoki\cqurator\fixtures\DispatcherFixture dispatcher <-
 * @property \spec\watoki\cqurator\fixtures\ClassFixture class <-
 */
class ExecuteQueryTest extends Specification {

    function testQueryReachesHandlerObject() {
        $this->class->givenTheClass('some\MyQuery');
        $this->dispatcher->givenAnObject('myHandler');
        $this->dispatcher->givenIAdded_AsHandlerFor('myHandler', 'some\MyQuery');

        $this->whenIExecuteTheQuery('some\MyQuery');
        $this->dispatcher->thenTheMethod_Of_ShouldBeInvoked('myQuery', 'myHandler');
    }

    ##########################################################################################

    private function whenIExecuteTheQuery($query) {
        $resource = new QueryResource($this->dispatcher->dispatcher);
        $resource->doGet($query);
    }

} 