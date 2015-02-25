<?php
namespace watoki\qrator\form\fields;

use watoki\qrator\form\TemplatedField;

class InputField extends TemplatedField {

    private $type;

    public function __construct($name, $type = 'text') {
        parent::__construct($name);
        $this->type = $type;
    }

    protected function getModel() {
        return array_merge(parent::getModel(), [
            'class' => $this->getClass(),
            'type' => $this->type
        ]);
    }

    /**
     * @return string
     */
    protected function getClass() {
        return 'form-control';
    }

}