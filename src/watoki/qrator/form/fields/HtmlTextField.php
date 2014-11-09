<?php
namespace watoki\qrator\form\fields;

class HtmlTextField extends TextField {

    protected function getClass() {
        return parent::getClass() . ' html-text-field';
    }

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
        return ["
            <script>
                $(document).ready(function() {
                    $('.html-text-field').summernote();
                });
            </script>"];
    }
}