<?php
namespace spec\watoki\cqurator\fixtures;

use watoki\curir\delivery\WebRequest;
use watoki\curir\protocol\Url;
use watoki\curir\responder\Redirecter;
use watoki\deli\Path;
use watoki\scrut\Fixture;

class ResourceFixture extends Fixture {

    /** @var WebRequest */
    public $request;

    /** @var mixed|\watoki\curir\Responder */
    public $returned;

    public function setUp() {
        parent::setUp();
        $this->request = new WebRequest(Url::fromString('http://example.com'), new Path());
    }

    public function givenTheRequestArgument_Is($key, $value) {
        $this->request->getArguments()->set($key, $value);
    }

    public function whenIDo_With($callback, $resource) {
        $this->returned = call_user_func($callback, $resource);
    }

    public function thenIShouldNotBeRedirected() {
    }

    public function thenIShouldBeRedirectedTo($url) {
        if ($this->returned instanceof Redirecter) {
            $this->spec->assertEquals($url, $this->returned->getTarget()->toString());
        } else {
            $this->spec->fail('Was not redirected');
        }
    }

    public function thenThereShouldBe_Of($count, $modelPath) {
        $this->spec->assertCount($count, $this->get($modelPath));
    }

    public function then_ShouldBe($modelPath, $value) {
        $this->spec->assertEquals($value, $this->get($modelPath));
    }

    public function thenThereShouldBeNo_In($key, $modelPath) {
        $this->spec->assertArrayNotHasKey($key, $this->get($modelPath));
    }

    private function get($modelPath) {
        $path = explode('/', $modelPath);
        $model = $this->returned;
        foreach ($path as $key) {
            $model = $model[$key];
        }
        return $model;
    }

} 