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
class HandleActionsTest extends Specification {

    function testActionReachesHandlerObject() {
        $this->class->givenTheClass('some\MyAction');
        $this->dispatcher->givenAnObject('myHandler');
        $this->dispatcher->givenIAdded_AsHandlerFor('myHandler', 'some\MyAction');

        $this->whenIDispatchTheAction('some\MyAction');
        $this->dispatcher->thenTheMethod_Of_ShouldBeInvoked('myAction', 'myHandler');
    }

    function testActionReachesHandlerClass() {
        $this->class->givenTheClass('classHandler\MyAction');
        $this->class->givenTheClass_WithTheBody('classHandler\Handler', '
            public static $executed = false;

            function myAction() {
                self::$executed = true;
            }
        ');
        $this->dispatcher->givenIAddedTheClass_AsHandlerFor('classHandler\Handler', 'classHandler\MyAction');

        $this->whenIDispatchTheAction('classHandler\MyAction');
        $this->class->then_ShouldBe('classHandler\Handler::$executed', true);
    }

    function testActionReachesHandlerClosure() {
        $this->class->givenTheClass('closureHandler\MyAction');
        $this->dispatcher->givenIAddedTheClosure_AsHandlerFor(function () {
            $GLOBALS['executed'] = true;
        }, 'closureHandler\MyAction');

        $this->whenIDispatchTheAction('closureHandler\MyAction');
        $this->class->then_ShouldBe('$GLOBALS["executed"]', true);
    }

    function testActionIsPassedAsArgument() {
        $this->class->givenTheClass('argument\MyAction');
        $this->dispatcher->givenAnObject('myHandler');
        $this->dispatcher->givenIAdded_AsHandlerFor('myHandler', 'argument\MyAction');

        $this->whenIDispatchTheAction('argument\MyAction');
        $this->dispatcher->thenTheMethodOf_ShouldBeInvokedWithAnInstanceOf('myHandler', 'argument\MyAction');
    }

    function testReturnedValueIsPassedBackAsSuccess() {
        $this->class->givenTheClass('success\MyAction');
        $this->dispatcher->givenIAddedTheClosure_AsHandlerFor(function () {
            return "Great Success!";
        }, 'success\MyAction');

        $this->whenIDispatchTheAction('success\MyAction');
        $this->thenTheResultShouldBeSuccessfulWith('Great Success!');
    }

    function testExceptionIsPassedBackAsException() {
        $this->class->givenTheClass('fail\MyAction');
        $this->dispatcher->givenIAddedTheClosure_AsHandlerFor(function () {
            throw new \Exception("Bam!");
        }, 'fail\MyAction');

        $this->whenIDispatchTheAction('fail\MyAction');
        $this->thenTheResultShouldFailWith('Bam!');

    }

    ##########################################################################################

    /** @var \watoki\smokey\Result */
    private $returned;

    private function whenIDispatchTheAction($action) {
        $this->returned = $this->dispatcher->dispatcher->fire(new $action);
    }

    private function thenTheResultShouldBeSuccessfulWith($value) {
        $returned = null;
        $this->returned->onSuccess(function ($found) use (&$returned) {
            $returned = $found;
        });
        $this->assertEquals($value, $returned);
    }

    private function thenTheResultShouldFailWith($message) {
        /** @var \Exception $exception */
        $exception = null;
        $this->returned->onException(function ($e) use (&$exception) {
            $exception = $e;
        });
        $this->assertInstanceOf('Exception', $exception);
        $this->assertEquals($message, $exception->getMessage());
    }

} 