<?php
namespace spec\watoki\qrator\fixtures;

use watoki\collections\Map;
use watoki\curir\cookie\CookieStore;
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

    /** @var Map */
    public $args;

    /** @var CookieStore */
    public $cookies;

    public function setUp() {
        parent::setUp();
        $this->request = new WebRequest(Url::fromString('http://example.com'), new Path());
        $this->args = new Map();
        $this->cookies = $this->spec->factory->setSingleton(CookieStore::class, new CookieStore(array()));
    }

    public function givenTheActionArgument_Is($key, $value) {
        $this->args->set($key, $value);
    }

    public function whenIDo_With($callback, $resourceClass) {
        $resource = $this->spec->factory->getInstance($resourceClass);
        $this->returned = call_user_func($callback, $resource);
    }

    public function thenIShouldNotBeRedirected() {
        if ($this->returned instanceof Redirecter) {
            $this->spec->fail("Was redirected to " . $this->returned->getTarget()->toString());
        }
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

    public function then_ShouldNotBe($modelPath, $value) {
        $this->spec->assertNotEquals($value, $this->get($modelPath));
    }

    public function then_ShouldContain($modelPath, $value) {
        $this->spec->assertContains($value, $this->get($modelPath));
    }

    public function thenThereShouldBeNo_In($key, $modelPath) {
        $this->spec->assertArrayNotHasKey($key, $this->get($modelPath));
    }

    public function thenThereShouldBeNo($key) {
        $this->spec->assertArrayNotHasKey($key, $this->returned);
    }

    public function get($modelPath) {
        if (is_object($this->returned)) {
            $this->spec->fail("Not an array: " . get_class($this->returned));
        }
        $path = explode('/', $modelPath);
        $model = $this->returned;
        foreach ($path as $key) {
            if (is_array($model) && array_key_exists($key, $model)) {
                $model = $model[$key];
            } else if (is_object($model)) {
                if (isset($model->$key)) {
                    $model = $model->$key;
                } else if (method_exists($model, $key)) {
                    $model = call_user_func(array($model, $key));
                }
            } else {
                $this->spec->fail("Cannot find [$key] in " . var_export($model, true));
            }
        }
        return $model;
    }

    public function thenItShouldReturn($value) {
        $this->spec->assertEquals($value, $this->returned);
    }

    public function then_ShouldExist($modelPath) {
        $this->spec->assertNotNull($this->get($modelPath));
    }

    public function thenThePayloadOfCookie_ShouldBe($name, $payload) {
        $cookie = $this->cookies->read($name);
        $this->spec->assertEquals($payload, $cookie->payload);
    }

}