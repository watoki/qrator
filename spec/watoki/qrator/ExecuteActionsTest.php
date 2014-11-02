<?php
namespace spec\watoki\qrator;

use watoki\qrator\representer\ActionGenerator;
use watoki\qrator\web\ExecuteResource;
use watoki\curir\cookie\Cookie;
use watoki\curir\cookie\CookieStore;
use watoki\curir\cookie\SerializerRepository;
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
        $this->dispatcher->givenIAddedTheClass_AsHandlerFor('reach\MyHandler', 'MyAction');

        $this->whenIExecuteTheAction('MyAction');
        $this->class->then_ShouldBe('$GLOBALS[\'handlerReached\']', true);
    }

    function testStoreLastActionInCookie() {
        $this->dispatcher->givenAnObject('myHandler');
        $this->dispatcher->givenIAdded_AsHandlerFor('myHandler', 'MyAction');

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

        $this->whenIExecuteTheAction('MyAction');
        $this->thenAnAlertShouldSay("Action executed successfully. You are now redirected to your last action.");
        $this->thenIShouldBeRedirectedTo_After_Seconds('execute?action=MyAction&args[one]=eins&args[two]=zwei', 3);
    }

    function testActionWithConstructorArguments() {
        $this->class->givenTheClass_WithTheBody('test\ConstructorAction', '
            function __construct($one, $two) {
                $GLOBALS["one"] = $one;
                $GLOBALS["two"] = $two;
            }
        ');
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

        $this->givenISet_With_ToFollowAfter('FollowUpAction', ['foo' => 'bar'], 'ActionWithFollowUp');

        $this->whenIExecuteTheAction('ActionWithFollowUp');
        $this->thenAnAlertShouldSay("Action executed successfully. Please stand by.");
        $this->thenIShouldBeRedirectedTo('FollowUpAction&args[foo]=bar');
    }

    function testFollowUpActionGeneratorGetResultOfFollowedAction() {
        $this->class->givenTheClass('ActionWithFollowUp');
        $this->class->givenTheClass('FollowUpAction');

        $this->dispatcher->givenIAddedTheClosure_AsHandlerFor(function () {
            return "baz";
        }, 'ActionWithFollowUp');

        $this->givenISet_WithTheCallback_ToFollowAfter('FollowUpAction', 'return ["foo" => $result];', 'ActionWithFollowUp');

        $this->whenIExecuteTheAction('ActionWithFollowUp');
        $this->thenAnAlertShouldSay("Action executed successfully. Please stand by.");
        $this->thenIShouldBeRedirectedTo('FollowUpAction&args[foo]=baz');
    }

    ####################################################################################################

    /** @var CookieStore */
    private $cookies;

    protected function setUp() {
        parent::setUp();
        $this->cookies = new CookieStore(new SerializerRepository(), array());
    }

    private function givenTheLastActionWas_WithArguments($action, $arguments) {
        $this->cookies->create(new Cookie([
            'action' => $action,
            'arguments' => $arguments
        ]), ExecuteResource::LAST_ACTION_COOKIE);
    }

    private function givenISet_With_ToFollowAfter($class, $args, $followed) {
        $this->givenISet_WithTheCallback_ToFollowAfter($class, function () use ($args) {
            return $args;
        }, $followed);
    }

    private function givenISet_WithTheCallback_ToFollowAfter($class, $callback, $followed) {
        if (is_string($callback)) {
            $callback = function ($result) use ($callback) {
                return eval($callback);
            };
        }
        $this->registry->givenIRegisteredAnActionRepresenterFor($followed);
        $this->registry->representers[$followed]->setFollowUpAction(new ActionGenerator($class, $callback));
    }

    private function whenIExecuteTheAction($action) {
        $this->resource->whenIDo_With(function (ExecuteResource $resource) use ($action) {
            return $resource->doGet($action, $this->resource->args);
        }, new ExecuteResource($this->factory, $this->dispatcher->dispatcher, $this->registry->registry, $this->cookies));
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

    private function thenIShouldBeRedirectedTo_After_Seconds($string, $seconds) {
        $this->resource->then_ShouldBe('redirect/content', "$seconds; URL=$string");
    }

    private function thenIShouldBeRedirectedTo($string) {
        $this->resource->then_ShouldContain('redirect/content', $string);
    }

} 