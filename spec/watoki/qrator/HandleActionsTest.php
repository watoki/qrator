<?php
namespace spec\watoki\qrator;

use watoki\scrut\Specification;

/**
 * An Action gets published over the ActionDispatcher and the result is passed back.
 *
 * @property \spec\watoki\qrator\fixtures\DispatcherFixture dispatcher <-
 * @property \spec\watoki\reflect\fixtures\ClassFixture class <-
 * @property \watoki\scrut\ExceptionFixture try <-
 * @property \spec\watoki\qrator\fixtures\RegistryFixture registry <-
 */
class HandleActionsTest extends Specification {

    function testActionReachesHandlerObject() {
        $this->class->givenTheClass('some\MyAction');
        $this->dispatcher->givenAnObject('myHandler');
        $this->dispatcher->givenISet_AsHandlerFor('myHandler', 'some\MyAction');

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
        $this->dispatcher->givenISet_AsHandlerFor('myHandler', 'argument\MyAction');

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

        $this->whenITryToDispatchTheAction('fail\MyAction');
        $this->try->thenTheException_ShouldBeThrown('Bam!');
    }

    function testCreateRepresentersOnTheFly() {
        /** @var \watoki\qrator\representer\generic\GenericEntityRepresenter $representer */
        $representer = $this->registry->registry->getEntityRepresenter(\DateTime::class);
        $representer->setStringifier(function (\DateTime $d) {
            return $d->format('Y-m-d');
        });

        $again = $this->registry->registry->getEntityRepresenter(\DateTime::class);
        $this->assertEquals('2001-02-03', $again->toString(new \DateTime('2001-02-03')));
    }

    ##########################################################################################

    private $result;

    private function whenIDispatchTheAction($action) {
        $this->result = $this->registry->representers[$action]->execute(new $action);
    }

    private function thenTheResultShouldBeSuccessfulWith($value) {
        $this->assertEquals($value, $this->result);
    }

    private function whenITryToDispatchTheAction($string) {
        $this->try->tryTo(function () use ($string) {
            $this->whenIDispatchTheAction($string);
        });
    }

} 