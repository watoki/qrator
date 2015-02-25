<?php
namespace watoki\qrator\form\fields;

class DateTimeField extends InputField {

    public function inflate($value) {
        if (!$value) {
            return null;
        }
        return new \DateTime($value);
    }

    protected function getClass() {
        return parent::getClass() . ' date-time-field';
    }

    public function addToHead() {
        if ($this->type != 'text') {
            return [];
        }
        return [
            'jquery' => self::ASSET_JQUERY,
            'jquery.datetimepicker' => '
                <link rel="stylesheet" type="text/css" href="assets/vendor/jquery.datetimepicker.css"/>
                <script src="assets/vendor/jquery.datetimepicker.js"></script>',
        ];
    }

    public function addToFoot() {
        if ($this->type != 'text') {
            return [];
        }
        return ["
            <script>
                $('.date-time-field').datetimepicker({
                  format:'Y-m-d H:i'
                });
            </script>"];
    }

} 