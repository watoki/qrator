<?php
namespace watoki\cqurator\form;

use watoki\curir\rendering\locating\ClassTemplateLocator;
use watoki\curir\rendering\PhpRenderer;

abstract class TemplatedField implements Field {

    /** @var string */
    protected $name;

    /** @var null|mixed */
    protected $value;

    /** @var bool */
    protected $required = false;

    /**
     * @param string $name
     */
    public function __construct($name) {
        $this->name = $name;
    }

    public function setRequired($to = true) {
        $this->required = $to;
    }

    public function isRequired() {
        return $this->required;
    }

    /**
     * @return string
     */
    public function getLabel() {
        return $this->name;
    }

    /**
     * @param mixed|null $value
     */
    public function setValue($value) {
        $this->value = $value;
    }

    /**
     * @return mixed|null
     */
    public function getValue() {
        return $this->value;
    }

    /**
     * @return string
     */
    public function render() {
        $locator = new ClassTemplateLocator($this);
        $renderer = $this->createRenderer();
        return $renderer->render($locator->find($this->getTemplateExtension()), $this->getModel());
    }

    /**
     * @return \watoki\curir\rendering\Renderer
     */
    protected function createRenderer() {
        return new PhpRenderer();
    }

    /**
     * @return string
     */
    protected function getTemplateExtension() {
        return 'phtml';
    }

    /**
     * @return array
     */
    protected function getModel() {
        return [
            'name' => "args[{$this->name}]",
            'value' => $this->getValue(),
            'required' => $this->required
        ];
    }
}