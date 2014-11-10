<?php
namespace spec\watoki\qrator\form\fields;

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
            <textarea id="test" name="args[test]" class="form-control" rows="5">Some text</textarea>');

        $this->field->thenItShouldAdd_ToTheHead(['jquery', 'bootstrap', 'font-awesome', 'summernote']);
        $this->field->thenItShouldAdd_ToTheFoot(["
            <script>
                $(document).ready(function() {
                    $('#test').summernote({
                      onkeyup: function() {
                        $('#test').val($('#test').code());
                      }
                    });
                    $('#test').css('display', 'block');
                    $('#test').css('visibility', 'hidden');
                    $('#test').css('height', '1px');
                    $('#test').css('padding', '0');
                    $('#test').css('margin', '0');
                });
            </script>"]);
    }

} 