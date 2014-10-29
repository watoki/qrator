<?php
namespace watoki\cqurator\form;

class StringField extends TemplatedField {

    /**
     * @param string $value
     * @return mixed
     */
    public function inflate($value) {
        return $value;
    }
}