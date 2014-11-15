<?php
namespace spec\watoki\qrator;

use watoki\collections\Map;
use watoki\curir\cookie\Cookie;
use watoki\curir\cookie\CookieSerializerRegistry;
use watoki\curir\cookie\CookieStore;
use watoki\qrator\web\ExecuteResource;
use watoki\scrut\Specification;

/**
 * @property \spec\watoki\qrator\fixtures\ResourceFixture resource <-
 * @property \spec\watoki\reflect\fixtures\ClassFixture class <-
 * @property \spec\watoki\qrator\fixtures\DispatcherFixture dispatcher <-
 */
class LeaveBreadcrumbsTest extends Specification {

    protected function background() {
        $this->class->givenTheClass('test\SomeAction');
        $this->dispatcher->givenISet_AsHandlerFor('myHandler', 'test\SomeAction');
    }

    function testStoreBreadcrumbsInCookie() {
        $this->class->givenTheClass_WithTheBody('test\FooAction', '
            public $foo;
        ');
        $this->resource->givenTheActionArgument_Is('foo', 'bar');

        $this->whenIExecuteTheAction('test\SomeAction');
        $this->thenTheBreadcrumbs_ShouldBeStored([
            ['Some Action', 'test\SomeAction', ['foo' => 'bar']]
        ]);
    }

    function testNoBreadcrumbsToShow() {
        $this->whenIExecuteTheAction('test\SomeAction');
        $this->thenThereShouldBeNoBreadcrumbs();
        $this->thenTheCurrentOneShouldBe('Some Action');
    }

    function testShowBreadcrumbs() {
        $this->givenTheStoredBreadcrumbs([
            ['one', 'first', []],
            ['two', 'second', []]
        ]);

        $this->whenIExecuteTheAction('test\SomeAction');
        $this->thenThereShouldBe_Breadcrumbs(2);
        $this->thenBreadcrumb_ShouldHaveTheCaption(1, 'one');
        $this->thenBreadcrumb_ShouldHaveTheLinkTarget(1, 'execute?action=first');
    }

    function testPushAction() {
        $this->givenTheStoredBreadcrumbs([
            ['one', 'first', []]
        ]);
        $this->whenIExecuteTheAction('test\SomeAction');
        $this->thenTheBreadcrumbs_ShouldBeStored([
            ['one', 'first', []],
            ['Some Action', 'test\SomeAction', []]
        ]);
    }

    function testPopAction() {
        $this->givenTheStoredBreadcrumbs([
            ['one', 'first', []],
            ['two', 'test\SomeAction', []],
            ['three', 'test\SomeAction', ['foo' => 'bar']],
            ['four', '?action=second']
        ]);
        $this->whenIExecuteTheAction('test\SomeAction');
        $this->thenTheBreadcrumbs_ShouldBeStored([
            ['one', 'first', []],
            ['Some Action', 'test\SomeAction', []]
        ]);
    }

    ################################################################################################

    /** @var CookieStore */
    private $cookies;

    private $args;

    protected function setUp() {
        parent::setUp();
        $this->cookies = new CookieStore(new CookieSerializerRegistry(), []);
        $this->args = new Map();
    }

    private function whenIExecuteTheAction($action) {
        $this->resource->whenIDo_With(function (ExecuteResource $resource) use ($action) {
            return $resource->doGet($action, $this->resource->args);
        }, new ExecuteResource($this->factory, $this->dispatcher->registry->registry, $this->cookies));
    }

    private function thenTheBreadcrumbs_ShouldBeStored($array) {
        $this->assertEquals($array, $this->cookies->read(ExecuteResource::BREADCRUMB_COOKIE)->payload);
    }

    private function thenThereShouldBeNoBreadcrumbs() {
        $this->thenThereShouldBe_Breadcrumbs(0);
    }

    private function givenTheStoredBreadcrumbs($array) {
        $this->cookies->create(new Cookie($array), ExecuteResource::BREADCRUMB_COOKIE);
    }

    private function thenThereShouldBe_Breadcrumbs($int) {
        $this->resource->thenThereShouldBe_Of($int, 'breadcrumbs/breadcrumb');
    }

    private function thenBreadcrumb_ShouldHaveTheCaption($int, $string) {
        $int--;
        $this->resource->then_ShouldBe("breadcrumbs/breadcrumb/$int/caption", $string);
    }

    private function thenBreadcrumb_ShouldHaveTheLinkTarget($int, $string) {
        $int--;
        $this->resource->then_ShouldBe("breadcrumbs/breadcrumb/$int/link/href", $string);
    }

    private function thenTheCurrentOneShouldBe($string) {
        $this->resource->then_ShouldBe('breadcrumbs/current', $string);
    }

} 