<?php
namespace watoki\qrator\form\fields;

use watoki\qrator\form\TemplatedField;

class HtmlEditorField extends TemplatedField {

    /**
     * @param string $value
     * @return mixed
     */
    public function inflate($value) {
        return $value;
    }

    /**
     * @return array
     */
    protected function getModel() {
        return [
            'name' => 'args[' . $this->getName() . ']',
            'value' => $this->getValue(),
        ];
    }

    public function addToHead() {
        return '
            <!-- include libries(jQuery, bootstrap, fontawesome) -->
            <script src="http://code.jquery.com/jquery-1.9.1.min.js"></script>
            <link href="http://netdna.bootstrapcdn.com/bootstrap/3.0.1/css/bootstrap.min.css" rel="stylesheet">
            <script src="http://netdna.bootstrapcdn.com/bootstrap/3.0.1/js/bootstrap.min.js"></script>
            <link href="http://netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.min.css" rel="stylesheet">

            <!-- include summernote css/js-->
            <link href="assets/summernote.css" rel="stylesheet">
            <script src="assets/summernote.min.js"></script>';
    }

    public function addToFoot() {
        return "
            <script>
                $(document).ready(function() {
                    $('.htmlEditor').summernote();
                });
            </script>";
    }
}