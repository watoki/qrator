<?php
namespace watoki\qrator\form\fields;

use watoki\qrator\form\Field;
use watoki\qrator\form\TemplatedField;

class ArrayField extends TemplatedField {

    /** @var \watoki\qrator\form\Field */
    private $innerField;

    public function __construct($name, Field $innerField) {
        parent::__construct($name);
        $this->innerField = $innerField;
    }

    /**
     * @param array $array
     * @return array
     */
    public function inflate($array) {
        array_pop($array);
        array_walk($array, function (&$value) {
            $value = $this->innerField->inflate($value);
        });
        return $array;
    }

    /**
     * @return \watoki\qrator\form\Field
     */
    public function getInnerField() {
        return $this->innerField;
    }

    /**
     * @return array
     */
    protected function getModel() {
        return [
            'inner' => $this->wrapInnerField(),
            'id' => uniqid()
        ];
    }

    private function wrapInnerField() {
        $rendered = $this->innerField->render();
        $find = "[" . $this->innerField->getName() . "]";
        return str_replace($find, "[{$this->getName()}][]", $rendered);
    }

}