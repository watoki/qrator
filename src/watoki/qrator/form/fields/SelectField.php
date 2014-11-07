<?php
namespace watoki\qrator\form\fields;

use watoki\qrator\form\TemplatedField;

class SelectField extends TemplatedField {

    private $options;

    public function __construct($name, $options = []) {
        parent::__construct($name);
        $this->options = $options;
    }

    /**
     * @return array Indexed by values
     */
    protected function getOptions() {
        return $this->options;
    }

    protected function getModel() {
        return array_merge(parent::getModel(), [
            'options' => $this->getOptions(),
            'selected' => $this->getValue()
        ]);
    }
}
