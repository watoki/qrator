<?php
namespace watoki\cqurator\form;

use watoki\curir\rendering\ClassTemplateLocator;
use watoki\curir\rendering\Locatable;
use watoki\curir\rendering\PhpRenderer;
use watoki\factory\Factory;

class TemplatedField implements Locatable, Field {

    /** @var string */
    private $name;

    /** @var null|mixed */
    private $value;

    /** @var Factory */
    private $factory;

    /**
     * @param Factory $factory <-
     * @param string $name
     */
    public function __construct(Factory $factory, $name) {
        $this->factory = $factory;
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
        $locator = new ClassTemplateLocator($this, $this->factory);
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
            'name' => $this->getLabel(),
            'value' => $this->getValue()
        ];
    }

    /**
     * @return string Directory of class
     */
    public function getDirectory() {
        $class = new \ReflectionClass($this);
        return dirname($class->getFileName());
    }

    /**
     * @return string Name of class with which it can be found (possibly omitting pre/suffixes)
     */
    public function getName() {
        $class = new \ReflectionClass($this);
        return $class->getShortName();
    }
}