<?php
namespace spec\watoki\qrator\fixtures;

use watoki\qrator\form\Field;
use watoki\scrut\Fixture;

/**
 * @property \watoki\scrut\ExceptionFixture try <-
 */
class FieldFixture extends Fixture {

    private $rendered;

    private $value;

    /** @var \watoki\qrator\form\Field */
    private $field;

    public function givenTheField(Field $field) {
        $this->field = $field;
    }

    public function whenIRenderTheField() {
        $this->field->setValue($this->value);
        $this->rendered = $this->field->render();
    }

    public function whenITryToRenderTheField() {
        $this->try->tryTo(function () {
            $this->whenIRenderTheField();
        });
    }

    public function thenTheOutputShouldBe($expected) {
        $expected = trim(preg_replace('/\n\s+/', "\n", $expected));
        $rendered = trim(preg_replace('/\n\s+/', "\n", $this->rendered));
        $this->spec->assertEquals($expected, $rendered);
    }

    public function givenTheValueIs($value) {
        $this->value = $value;
    }

    public function thenTheOutputShouldContain($string) {
        $this->spec->assertContains($string, $this->rendered);
    }

    public function thenTheOutputShouldNotContain($string) {
        $this->spec->assertNotContains($string, $this->rendered);
    }

} 