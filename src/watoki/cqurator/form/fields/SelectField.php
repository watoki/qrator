<?php
namespace watoki\cqurator\form\fields;

use watoki\cqurator\form\TemplatedField;

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
        return [
            'name' => "args[{$this->name}]",
            'required' => $this->isRequired(),
            'options' => $this->getOptions(),
            'selected' => $this->getValue()
        ];
    }

    /**
     * @param string $value
     * @return mixed
     */
    public function inflate($value) {
        return $value;
    }
}
