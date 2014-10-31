<?php
namespace watoki\cqurator\form\fields;

use watoki\cqurator\form\Field;
use watoki\cqurator\form\TemplatedField;

class ArrayField extends TemplatedField {

    /** @var \watoki\cqurator\form\Field */
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