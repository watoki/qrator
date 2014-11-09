<?php
namespace spec\watoki\qrator\form;

use watoki\qrator\form\fields\HtmlTextField;
use watoki\scrut\Specification;

/**
 * @property \spec\watoki\qrator\fixtures\FieldFixture field <-
 */
class HtmlTextFieldTest extends Specification {

    function testRenderWithWysiwygEditor() {
        $this->field->givenTheField(new HtmlTextField('test'));
        $this->field->givenTheValueIs('Some text');
        $this->field->whenIRenderTheField();
        $this->field->thenTheOutputShouldBe('
            <label for="test">Test</label>
            <textarea id="test" name="args[test]" class="html-text-field" rows="5">Some text</textarea>');
        $this->field->thenItShouldAdd_ToTheHead(['jquery', 'bootstrap', 'font-awesome', 'summernote']);
        $this->field->thenItShouldAdd_ToTheFoot(["
            <script>
                $(document).ready(function() {
                    $('.html-text-field').summernote();
                });
            </script>"]);
    }

} 