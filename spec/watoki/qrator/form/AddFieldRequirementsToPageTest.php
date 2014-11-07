<?php
namespace spec\watoki\qrator\form;

use watoki\dom\Parser;
use watoki\dom\Printer;
use watoki\qrator\web\PrepareResource;
use watoki\scrut\Specification;

/**
 * @property \spec\watoki\qrator\fixtures\ClassFixture class <-
 * @property \spec\watoki\qrator\fixtures\RegistryFixture registry <-
 * @property \spec\watoki\qrator\fixtures\ResourceFixture resource <-
 */
class AddFieldRequirementsToPageTest extends Specification {

    function testAddRequirementsOfOneField() {
        $this->class->givenTheClass_Extending_WithTheBody('fieldRequirements\SomeField', '\watoki\qrator\form\Field', '
            public function render() { return "Field"; }
            public function addToHead() { return "<meta name=\"this is so meta\"/>"; }
            public function addToFoot() { return "<script src=\"someScript.js\"/>"; }
        ');
        $this->class->givenTheClass_WithTheBody('fieldRequirements\SomeAction', '
            public $someProperty;
            public $otherProperty;
        ');

        $this->registry->givenIRegisteredAnActionRepresenterFor('fieldRequirements\SomeAction');
        $this->registry->givenISetTheField_Of_ToBeAnInstanceOf('someProperty', 'fieldRequirements\SomeAction',
            'fieldRequirements\SomeField');
        $this->registry->givenISetTheField_Of_ToBeAnInstanceOf('otherProperty', 'fieldRequirements\SomeAction',
            'fieldRequirements\SomeField');

        $this->whenIPrepare('fieldRequirements\SomeAction');
        $this->thenTheHeadShouldContains('<meta name="this is so meta"/>');
        $this->thenTheFootShouldBe('<script src="someScript.js"/>');
    }

    ##########################################################################################

    /** @var \watoki\dom\Element */
    private $dom;

    private function whenIPrepare($action) {
        $this->resource->whenIDo_With(function (PrepareResource $resource) use ($action) {
            $model = $resource->doGet($action);
            $this->resource->request->getFormats()->append('html');

            $rendered = $resource->after($model, $this->resource->request)->getBody();
            $parser = new Parser($rendered);
            $this->dom = $parser->getRoot();

            return $model;
        }, new PrepareResource($this->factory, $this->registry->registry));
    }

    private function thenTheHeadShouldContains($string) {
        $printer = new Printer();
        $headContent = $printer->printNode($this->dom->findChildElement('html')->findChildElement('head'));
        $this->assertContains($string, $headContent);
    }

    private function thenTheFootShouldBe($string) {
        $printer = new Printer();
        $footerContent = $printer->printNodes($this->dom
                ->findChildElement('html')
                ->findChildElement('body')
                ->findChildElement('footer')
                ->getChildren()
        );
        $this->assertEquals($string, $footerContent);
    }

} 