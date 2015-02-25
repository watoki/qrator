<?php
namespace watoki\qrator\form\fields;

use watoki\qrator\form\TemplatedField;

class TextField extends TemplatedField {

    protected function getModel() {
        return array_merge(parent::getModel(), [
            'class' => $this->getClass(),
            'rows' => '5'
        ]);
    }

    /**
     * @return string
     */
    protected function getClass() {
        return 'form-control';
    }

}