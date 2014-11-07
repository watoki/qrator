<?php
namespace watoki\qrator\form\fields;

use watoki\qrator\form\TemplatedField;

class HiddenField extends TemplatedField {

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
            'value' => $this->getValue()
        ];
    }
}