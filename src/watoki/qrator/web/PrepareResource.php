<?php
namespace watoki\qrator\web;

use watoki\collections\Map;
use watoki\factory\exception\InjectionException;
use watoki\qrator\ActionRepresenter;
use watoki\qrator\form\Field;
use watoki\qrator\form\fields\HiddenField;
use watoki\qrator\Representer;

class PrepareResource extends ActionResource {

    protected function redirectToPrepare($action, Map $args) {
        throw new \LogicException('Cannot redirect. Already at prepare.');
    }

    /**
     * @param string $action
     * @param null|\watoki\collections\Map $args
     * @return array
     */
    public function doGet($action, Map $args = null) {
        $args = $args ? : new Map();

        $representer = $this->registry->getActionRepresenter($action);

        try {
            $object = $representer->create($args);

            if (!$representer->hasMissingProperties($object)) {
                return $this->redirectTo('execute', $args, ['action' => $action]);
            }
        } catch (InjectionException $e) {
            $object = $action;
        }

        return [
            'form' => $this->assembleForm($object)
        ];
    }

    private function assembleForm($action) {
        $representer = $this->registry->getActionRepresenter($action);

        $parameters = [
            ['name' => 'action', 'value' => $representer->getClass()],
        ];

        if (is_object($action)) {
            $representer->preFill($action);
        }

        $form = [
            'title' => $representer->getName(),
            'action' => 'execute',
            'parameter' => $parameters,
            'field' => $this->assembleFields($action, $representer)
        ];
        return $form;
    }

    private function assembleFields($action, ActionRepresenter $representer) {
        return array_map(function (Field $field) {
            return [
                'label' => ($field instanceof HiddenField) ? null : ucfirst($field->getLabel()),
                'control' => $field->render(),
                'isRequired' => $field->isRequired()
            ];
        }, $representer->getFields($action));
    }
}