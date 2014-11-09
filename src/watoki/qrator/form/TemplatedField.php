<?php
namespace watoki\qrator\form;

use watoki\curir\rendering\locating\ClassTemplateLocator;
use watoki\curir\rendering\PhpRenderer;

abstract class TemplatedField extends Field {

    const ASSET_JQUERY = '
                <script src="http://code.jquery.com/jquery-1.9.1.min.js"></script>';

    const ASSET_BOOTSTRAP = '
                <link href="http://netdna.bootstrapcdn.com/bootstrap/3.0.1/css/bootstrap.min.css" rel="stylesheet">
                <script src="http://netdna.bootstrapcdn.com/bootstrap/3.0.1/js/bootstrap.min.js"></script>';

    const ASSSET_FONT_AWESOME = '
                <link href="http://netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.min.css" rel="stylesheet">';

    /**
     * @return array
     */
    protected function getModel() {
        return [
            'label' => '<label for="' . $this->getName() . '">' . $this->getLabel() . ($this->isRequired() ? '*' : '') . '</label>' . "\n",
            'labelText' => $this->getLabel(),
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