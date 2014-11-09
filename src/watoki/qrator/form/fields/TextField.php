<?php
namespace watoki\qrator\form\fields;

class TextField extends StringField {

    protected function getModel() {
        return array_merge(parent::getModel(), [
            'rows' => '5'
        ]);
    }


}