<?php
namespace spec\watoki\cqurator;
use watoki\cqurator\web\CommandResource;
use watoki\cqurator\web\QueryResource;
use watoki\curir\cookie\Cookie;
use watoki\curir\cookie\CookieStore;
use watoki\curir\cookie\SerializerRepository;
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

        $this->whenIExecuteTheCommand('MyCommand');
        $this->resource->thenItShouldReturn('Command executed: MyCommand');
        $this->class->then_ShouldBe('$GLOBALS[\'handlerReached\']', true);
    }

    function testStoreLastQueryInCookie() {
        $this->class->givenTheClass('MyQuery');

        $this->dispatcher->givenAnObject('myHandler');
        $this->dispatcher->givenIAdded_AsHandlerFor('myHandler', 'MyQuery');

        $this->resource->givenTheActionArgument_Is('one', 'uno');
        $this->resource->givenTheActionArgument_Is('two', 'dos');

        $this->whenIExecuteTheQuery('MyQuery');
        $this->thenTheCookie_WithTheValue_ShouldBeStored('lastQuery', [
            'action' => 'MyQuery',
            'arguments' => [
                'one' => 'uno',
                'two' => 'dos'
            ]
        ]);
    }

    function testRedirectToLastQueryFromCookie() {
        $this->class->givenTheClass('MyCommand');
        $this->class->givenTheClass('MyQuery');
        $this->givenTheCookie_WithValue('lastQuery', [
            'action' => 'MyQuery',
            'arguments' => [
                'one' => 'eins',
                'two' => 'zwei'
            ]
        ]);

        $this->whenIExecuteTheCommand('MyCommand');
        $this->resource->thenIShouldBeRedirectedTo('query?action=MyQuery&args[one]=eins&args[two]=zwei');
    }

    ####################################################################################################

    /** @var CookieStore */
    private $cookies;

    protected function setUp() {
        parent::setUp();
        $this->cookies = new CookieStore(new SerializerRepository(), array());
    }

    private function whenIExecuteTheCommand($command) {
        $this->resource->whenIDo_With(function (CommandResource $resource) use ($command) {
            return $resource->doPost($command, $this->resource->args);
        }, new CommandResource($this->factory, $this->dispatcher->dispatcher, $this->registry->registry, $this->cookies));
    }

    private function whenIExecuteTheQuery($query) {
        $this->resource->whenIDo_With(function (QueryResource $resource) use ($query) {
            return $resource->doGet($query, $this->resource->args);
        }, new QueryResource($this->factory, $this->dispatcher->dispatcher, $this->registry->registry, $this->cookies));
    }

    private function thenTheCookie_WithTheValue_ShouldBeStored($name, $value) {
        $cookie = $this->cookies->read($name);
        $this->assertEquals($value, $cookie->payload);
    }

    private function givenTheCookie_WithValue($name, $value) {
        $this->cookies->create(new Cookie($value), $name);
    }

} 