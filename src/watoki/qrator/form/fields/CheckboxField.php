<?php
namespace watoki\qrator\form\fields;

use watoki\qrator\form\TemplatedField;

class CheckboxField extends TemplatedField {

    public function inflate($value) {
        return !!$value;
    }

} 