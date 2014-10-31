<?php
namespace spec\watoki\cqurator;

use watoki\collections\Map;
use watoki\cqurator\RepresenterRegistry;
use watoki\cqurator\web\QueryResource;
use watoki\curir\cookie\Cookie;
use watoki\curir\cookie\CookieStore;
use watoki\curir\cookie\SerializerRepository;
use watoki\scrut\Specification;

/**
 * @property \spec\watoki\cqurator\fixtures\ResourceFixture resource <-
 * @property \spec\watoki\cqurator\fixtures\ClassFixture class <-
 * @property \spec\watoki\cqurator\fixtures\DispatcherFixture dispatcher <-
 */
class LeaveBreadcrumbsTest extends Specification {

    protected function background() {
        $this->class->givenTheClass('test\SomeQuery');
        $this->dispatcher->givenIAdded_AsHandlerFor('myHandler', 'test\SomeQuery');
    }

    function testStoreBreadcrumbsInCookie() {
        $this->class->givenTheClass_WithTheBody('test\FooQuery', '
            public $foo;
        ');
        $this->resource->givenTheActionArgument_Is('foo', 'bar');

        $this->whenIExecuteTheQuery('test\SomeQuery');
        $this->thenTheBreadcrumbs_ShouldBeStored([
            ['SomeQuery', 'test\SomeQuery', ['foo' => 'bar']]
        ]);
    }

    function testNoBreadcrumbsToShow() {
        $this->whenIExecuteTheQuery('test\SomeQuery');
        $this->thenThereShouldBeNoBreadcrumbs();
        $this->thenTheCurrentOneShouldBe('SomeQuery');
    }

    function testShowBreadcrumbs() {
        $this->givenTheStoredBreadcrumbs([
            ['one', 'first', []],
            ['two', 'second', []]
        ]);

        $this->whenIExecuteTheQuery('test\SomeQuery');
        $this->thenThereShouldBe_Breadcrumbs(2);
        $this->thenBreadcrumb_ShouldHaveTheCaption(1, 'one');
        $this->thenBreadcrumb_ShouldHaveTheLinkTarget(1, 'query?action=first');
    }

    function testPushQuery() {
        $this->givenTheStoredBreadcrumbs([
            ['one', 'first', []]
        ]);
        $this->whenIExecuteTheQuery('test\SomeQuery');
        $this->thenTheBreadcrumbs_ShouldBeStored([
            ['one', 'first', []],
            ['SomeQuery', 'test\SomeQuery', []]
        ]);
    }

    function testPopQuery() {
        $this->givenTheStoredBreadcrumbs([
            ['one', 'first', []],
            ['SomeQuery', 'test\SomeQuery', []],
            ['SomeQuery', 'test\SomeQuery', ['foo' => 'bar']],
            ['two', '?action=second']
        ]);
        $this->whenIExecuteTheQuery('test\SomeQuery');
        $this->thenTheBreadcrumbs_ShouldBeStored([
            ['one', 'first', []],
            ['SomeQuery', 'test\SomeQuery', []]
        ]);
    }

    ################################################################################################

    /** @var CookieStore */
    private $cookies;

    protected function setUp() {
        parent::setUp();
        $this->cookies = new CookieStore(new SerializerRepository(), []);
        $this->args = new Map();
    }

    private function whenIExecuteTheQuery($query) {
        $this->resource->whenIDo_With(function (QueryResource $resource) use ($query) {
            return $resource->doGet($query, $this->resource->args);
        }, new QueryResource($this->factory, $this->dispatcher->dispatcher,
            new RepresenterRegistry($this->factory), $this->cookies));
    }

    private function thenTheBreadcrumbs_ShouldBeStored($array) {
        $this->assertEquals($array, $this->cookies->read(QueryResource::BREADCRUMB_COOKIE)->payload);
    }

    private function thenThereShouldBeNoBreadcrumbs() {
        $this->thenThereShouldBe_Breadcrumbs(0);
    }

    private function givenTheStoredBreadcrumbs($array) {
        $this->cookies->create(new Cookie($array), QueryResource::BREADCRUMB_COOKIE);
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