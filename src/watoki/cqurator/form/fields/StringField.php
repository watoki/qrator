<?php
namespace watoki\cqurator\form\fields;

use watoki\cqurator\form\TemplatedField;

class StringField extends TemplatedField {

    /**
     * @param string $value
     * @return mixed
     */
    public function inflate($value) {
        return $value;
    }
}