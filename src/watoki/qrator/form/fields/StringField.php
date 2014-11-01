<?php
namespace watoki\qrator\form\fields;

use watoki\qrator\form\TemplatedField;

class StringField extends TemplatedField {

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
            'name' => "args[{$this->name}]",
            'value' => $this->getValue(),
            'required' => $this->isRequired()
        ];
    }
}