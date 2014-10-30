<?php
namespace spec\watoki\cqurator;
use watoki\cqurator\web\CommandResource;
use watoki\scrut\Specification;

/**
 * Command requests are prepared, executed and then redirected to the last Query.
 *
 * @property \spec\watoki\cqurator\fixtures\ClassFixture class <-
 * @property \spec\watoki\cqurator\fixtures\DispatcherFixture dispatcher <-
 * @property \spec\watoki\cqurator\fixtures\ResourceFixture resource <-
 * @property \spec\watoki\cqurator\fixtures\RegistryFixture registry <-
 */
class ExecuteCommandsTest extends Specification {

    function testCommandReachesHandler() {
        $this->class->givenTheClass('MyCommand');
        $this->class->givenTheClass_WithTheBody('reach\MyHandler', '
            function myCommand() {
                $GLOBALS["handlerReached"] = true;
            }
        ');
        $this->dispatcher->givenIAddedTheClass_AsHandlerFor('reach\MyHandler', 'MyCommand');

        $this->whenIExecuted('MyCommand');
        $this->class->then_ShouldBe('$GLOBALS[\'handlerReached\']', true);
    }

    function testGetRequestsAreNotAllowed() {
        $this->markTestIncomplete();
    }

    function testStoreLastQueryInCookie() {
        $this->markTestIncomplete();
    }

    function testRedirectToLastQueryFromCookie() {
        $this->markTestIncomplete();
    }

    ####################################################################################################

    private function whenIExecuted($command) {
        $this->resource->whenIDo_With(function (CommandResource $resource) use ($command) {
            return $resource->doPost($this->resource->request, $command);
        }, new CommandResource($this->factory, $this->dispatcher->dispatcher, $this->registry->registry));
    }

} 