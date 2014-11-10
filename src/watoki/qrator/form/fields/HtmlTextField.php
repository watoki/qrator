<?php
namespace watoki\qrator\form\fields;

class HtmlTextField extends TextField {

    public function addToHead() {
        return [
            'jquery' => self::ASSET_JQUERY,
            'bootstrap' => self::ASSET_BOOTSTRAP,
            'font-awesome' => self::ASSSET_FONT_AWESOME,
            'summernote' => '
                <link href="assets/vendor/summernote.css" rel="stylesheet">
                <script src="assets/vendor/summernote.min.js"></script>'];
    }

    public function addToFoot() {
        $id = $this->getName();
        return ["
            <script>
                $(document).ready(function() {
                    $('#$id').summernote({
                      onkeyup: function() {
                        $('#$id').val($('#$id').code());
                      }
                    });
                    $('#$id').css('display', 'block');
                    $('#$id').css('visibility', 'hidden');
                    $('#$id').css('height', '1px');
                    $('#$id').css('padding', '0');
                    $('#$id').css('margin', '0');
                });
            </script>"];
    }
}