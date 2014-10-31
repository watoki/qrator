<?php
namespace watoki\cqurator\form;

use watoki\curir\rendering\locating\ClassTemplateLocator;
use watoki\curir\rendering\PhpRenderer;

abstract class TemplatedField implements Field {

    /** @var string */
    protected $name;

    /** @var null|mixed */
    private $value;

    /** @var bool */
    private $required = false;

    /**
     * @param string $name
     */
    public function __construct($name) {
        $this->name = $name;
    }

    /**
     * @return array
     */
    abstract protected function getModel();

    public function setRequired($to = true) {
        $this->required = $to;
    }

    public function isRequired() {
        return $this->required;
    }

    public function getName() {
        return $this->name;
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
        $model = $this->getModel();
        return $renderer->render($locator->find($this->getTemplateExtension()), $model);
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
}