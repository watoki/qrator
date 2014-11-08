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
        return array_merge(parent::getModel(), [
            'inner' => $this->wrapInnerField(),
        ]);
    }

    private function wrapInnerField() {
        $rendered = $this->innerField->render();
        $rendered = preg_replace('#<label.+</label>#', '', $rendered);
        $rendered = str_replace('id="' . $this->innerField->getName() . '"', '', $rendered);
        $rendered = str_replace("[" . $this->innerField->getName() . "]", "[{$this->getName()}][]", $rendered);
        return $rendered;
    }

}