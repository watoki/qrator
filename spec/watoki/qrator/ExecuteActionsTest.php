<?php
namespace spec\watoki\qrator;

use watoki\curir\cookie\Cookie;
use watoki\curir\cookie\CookieSerializerRegistry;
use watoki\curir\cookie\CookieStore;
use watoki\qrator\representer\ActionLink;
use watoki\qrator\web\ExecuteResource;
use watoki\scrut\Specification;

/**
 * Action requests are prepared, executed and then either the result is shown or the user is redirected to the last Action.
 *
 * @property \spec\watoki\qrator\fixtures\ClassFixture class <-
 * @property \spec\watoki\qrator\fixtures\DispatcherFixture dispatcher <-
 * @property \spec\watoki\qrator\fixtures\ResourceFixture resource <-
 * @property \spec\watoki\qrator\fixtures\RegistryFixture registry <-
 */
class ExecuteActionsTest extends Specification {

    function testActionReachesHandler() {
        $this->class->givenTheClass('MyAction');
        $this->class->givenTheClass_WithTheBody('reach\MyHandler', '
            function myAction() {
                $GLOBALS["handlerReached"] = true;
            }
        ');
        $this->registry->givenIRegisteredAnActionRepresenterFor('MyAction');
        $this->dispatcher->givenIAddedTheClass_AsHandlerFor('reach\MyHandler', 'MyAction');

        $this->whenIExecuteTheAction('MyAction');
        $this->class->then_ShouldBe('$GLOBALS[\'handlerReached\']', true);
    }

    function testStoreLastActionInCookie() {
        $this->class->givenTheClass('MyAction');
        $this->dispatcher->givenAnObject('myHandler');
        $this->registry->givenIRegisteredAnActionRepresenterFor('MyAction');
        $this->dispatcher->givenISet_AsHandlerFor('myHandler', 'MyAction');

        $this->resource->givenTheActionArgument_Is('one', 'uno');
        $this->resource->givenTheActionArgument_Is('two', 'dos');

        $this->whenIExecuteTheAction('MyAction');
        $this->then_WithArguments_ShouldBeStoredAsLastAction('MyAction', [
            'one' => 'uno',
            'two' => 'dos'
        ]);
    }

    function testRedirectToLastActionFromCookie() {
        $this->givenTheLastActionWas_WithArguments('MyAction', [
            'one' => 'eins',
            'two' => 'zwei'
        ]);
        $this->registry->givenIRegisteredAnActionRepresenterFor('MyAction');
        $this->dispatcher->givenISetAnEmptyHandlerFor('MyAction');

        $this->whenIExecuteTheAction('MyAction');
        $this->thenIShouldBeRedirectedTo('execute?action=MyAction&args[one]=eins&args[two]=zwei');
    }

    function testDoNotRedirectIfResultIsEmptyArray() {
        $this->givenTheLastActionWas_WithArguments('MyAction', [
            'one' => 'eins',
            'two' => 'zwei'
        ]);
        $this->dispatcher->givenIAddedTheClosure_AsHandlerFor(function () {
            return [];
        }, 'MyAction');

        $this->whenIExecuteTheAction('MyAction');
        $this->thenAnAlertShouldSay('Empty result.');
    }

    function testActionWithConstructorArguments() {
        $this->class->givenTheClass_WithTheBody('test\ConstructorAction', '
            function __construct($one, $two) {
                $GLOBALS["one"] = $one;
                $GLOBALS["two"] = $two;
            }
        ');

        $this->registry->givenIRegisteredAnActionRepresenterFor('test\ConstructorAction');
        $this->dispatcher->givenISetAnEmptyHandlerFor('test\ConstructorAction');

        $this->resource->givenTheActionArgument_Is('one', 'uno');
        $this->resource->givenTheActionArgument_Is('two', 'dos');

        $this->whenIExecuteTheAction('test\ConstructorAction');
        $this->class->then_ShouldBe('$GLOBALS[\'one\']', 'uno');
        $this->class->then_ShouldBe('$GLOBALS[\'two\']', 'dos');
    }

    function testMissingConstructorArguments() {
        $this->class->givenTheClass_WithTheBody('test\MissingConstructorArguments', '
            function __construct($one, $two) {}
        ');

        $this->whenIExecuteTheAction('test\MissingConstructorArguments');
        $this->resource->thenIShouldBeRedirectedTo('prepare?action=test%5CMissingConstructorArguments');
    }

    function testFollowUpAction() {
        $this->class->givenTheClass('ActionWithFollowUp');
        $this->class->givenTheClass('FollowUpAction');

        $this->givenISet_ToFollowAfter('FollowUpAction', 'ActionWithFollowUp');

        $this->dispatcher->givenISetAnEmptyHandlerFor('ActionWithFollowUp');

        $this->whenIExecuteTheAction('ActionWithFollowUp');
        $this->thenIShouldBeRedirectedTo('execute?action=FollowUpAction');
    }

    function testFollowUpActionGeneratorGetResultOfFollowedAction() {
        $this->class->givenTheClass_WithTheBody('FollowUpActionWithProperty',
            'public $foo;');
        $this->class->givenTheClass('FollowUpAction');

        $this->givenISet_ToFollowAfter_With('FollowUpActionWithProperty', 'ActionWithFollowUp', '["foo" => $result]');

        $this->dispatcher->givenIAddedTheClosure_AsHandlerFor(function () {
            return "baz";
        }, 'ActionWithFollowUp');

        $this->whenIExecuteTheAction('ActionWithFollowUp');
        $this->thenIShouldBeRedirectedTo('execute?action=FollowUpActionWithProperty&args[foo]=baz');
    }

    ####################################################################################################

    /** @var CookieStore */
    private $cookies;

    protected function setUp() {
        parent::setUp();
        $this->cookies = new CookieStore(new CookieSerializerRegistry(), array());
    }

    private function givenTheLastActionWas_WithArguments($action, $arguments) {
        $this->cookies->create(new Cookie([
            'action' => $action,
            'arguments' => $arguments
        ]), ExecuteResource::LAST_ACTION_COOKIE);
    }

    private function givenISet_ToFollowAfter($action, $followed) {
        $this->givenISet_ToFollowAfter_With($action, $followed, "[]");
    }

    private function givenISet_ToFollowAfter_With($action, $followed, $arguments) {
        $this->registry->givenIRegisteredAnActionRepresenterFor($followed);
        /** @noinspection PhpUnusedParameterInspection */
        $this->registry->representers[$followed]->setFollowUpAction(function ($result) use ($action, $arguments) {
            return new ActionLink($action, eval('return ' . $arguments . ';'));
        });
    }

    private function whenIExecuteTheAction($action) {
        $this->resource->whenIDo_With(function (ExecuteResource $resource) use ($action) {
            return $resource->doGet($action, $this->resource->args);
        }, new ExecuteResource($this->factory, $this->registry->registry, $this->cookies));
    }

    private function then_WithArguments_ShouldBeStoredAsLastAction($action, $arguments) {
        $cookie = $this->cookies->read(ExecuteResource::LAST_ACTION_COOKIE);
        $this->assertEquals([
            'action' => $action,
            'arguments' => $arguments
        ], $cookie->payload);
    }

    private function thenAnAlertShouldSay($string) {
        $this->resource->then_ShouldBe('alert', $string);
    }

    private function thenIShouldBeRedirectedTo($string) {
        $this->resource->thenIShouldBeRedirectedTo($string);
    }

} 