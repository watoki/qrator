<?php
namespace watoki\cqurator\form;

use watoki\curir\rendering\locating\ClassTemplateLocator;
use watoki\curir\rendering\PhpRenderer;

abstract class TemplatedField implements Field {

    /** @var string */
    private $name;

    /** @var null|mixed */
    private $value;

    /**
     * @param string $name
     */
    public function __construct($name) {
        $this->name = $name;
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
            'value' => $this->getValue()
        ];
    }
}