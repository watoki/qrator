<?php
namespace watoki\qrator\form\fields;

class DateTimeField extends StringField {

    public function inflate($value) {
        return new \DateTime($value);
    }

    protected function getClass() {
        return parent::getClass() . ' date-time-field';
    }

    public function addToHead() {
        return [
            'jquery' => self::ASSET_JQUERY,
            'jquery.datetimepicker' => '
                <link rel="stylesheet" type="text/css" href="assets/vendor/jquery.datetimepicker.css"/>
                <script src="assets/vendor/jquery.datetimepicker.js"></script>',
        ];
    }

    public function addToFoot() {
        return ["
            <script>
                $('.date-time-field').datetimepicker();
            </script>"];
    }

} 