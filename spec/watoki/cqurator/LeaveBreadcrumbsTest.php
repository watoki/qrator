<?php
namespace spec\watoki\cqurator;

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
        $this->resource->givenTheRequestArgument_Is('foo', 'bar');

        $this->whenIExecuteTheQuery('test\SomeQuery');
        $this->thenTheBreadcrumbs_ShouldBeStored([
            ['SomeQuery', 'query?action=test%5CSomeQuery&foo=bar']
        ]);
    }

    function testNoBreadcrumbsToShow() {
        $this->whenIExecuteTheQuery('test\SomeQuery');
        $this->thenThereShouldBeNoBreadcrumbs();
        $this->thenTheCurrentOneShouldBe('SomeQuery');
    }

    function testShowBreadcrumbs() {
        $this->givenTheStoredBreadcrumbs([
            ['one', 'first'],
            ['two', 'second']
        ]);

        $this->whenIExecuteTheQuery('test\SomeQuery');
        $this->thenThereShouldBe_Breadcrumbs(2);
        $this->thenBreadcrumb_ShouldHaveTheCaption(1, 'one');
        $this->thenBreadcrumb_ShouldHaveTheLinkTarget(1, 'first');
    }

    function testPushQuery() {
        $this->givenTheStoredBreadcrumbs([
            ['one', 'first']
        ]);
        $this->whenIExecuteTheQuery('test\SomeQuery');
        $this->thenTheBreadcrumbs_ShouldBeStored([
            ['one', 'first'],
            ['SomeQuery', 'query?action=test%5CSomeQuery']
        ]);
    }

    function testPopQuery() {
        $this->givenTheStoredBreadcrumbs([
            ['one', 'first'],
            ['SomeQuery', 'query?action=test%5CSomeQuery'],
            ['SomeQuery', 'query?action=test%5CSomeQuery&foo=bar'],
            ['two', 'second']
        ]);
        $this->whenIExecuteTheQuery('test\SomeQuery');
        $this->thenTheBreadcrumbs_ShouldBeStored([
            ['one', 'first'],
            ['SomeQuery', 'query?action=test%5CSomeQuery']
        ]);
    }

    ################################################################################################

    /** @var CookieStore */
    private $cookies;

    protected function setUp() {
        parent::setUp();
        $this->cookies = new CookieStore(new SerializerRepository(), []);
    }

    private function whenIExecuteTheQuery($query) {
        $this->resource->whenIDo_With(function (QueryResource $resource) use ($query) {
            $this->resource->request->getArguments()->set('action', $query);
            return $resource->doGet($this->resource->request, $query);
        }, new QueryResource($this->factory, $this->dispatcher->dispatcher, new RepresenterRegistry(), $this->cookies));
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