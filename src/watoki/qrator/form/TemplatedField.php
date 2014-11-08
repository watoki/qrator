<?php
namespace watoki\qrator\form;

use watoki\curir\rendering\locating\ClassTemplateLocator;
use watoki\curir\rendering\PhpRenderer;

abstract class TemplatedField extends Field {

    /**
     * @return array
     */
    protected function getModel() {
        return [
            'label' => $this->getLabel(),
            'name' => 'args[' . $this->getName() . ']',
            'value' => $this->getValue(),
            'required' => $this->isRequired(),
            'id' => $this->getName()
        ];
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