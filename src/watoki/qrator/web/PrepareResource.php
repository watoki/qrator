<?php
namespace watoki\qrator\web;

use watoki\collections\Map;
use watoki\qrator\ActionRepresenter;
use watoki\qrator\form\Field;
use watoki\qrator\form\PreFilling;
use watoki\qrator\Representer;
use watoki\factory\exception\InjectionException;

class PrepareResource extends ActionResource {

    protected function redirectToPrepare($action, Map $args, $type) {
        throw new \LogicException('Cannot redirect. Already at prepare.');
    }

    /**
     * @param string $action
     * @param string $type
     * @param null|\watoki\collections\Map $args
     * @return array
     */
    public function doGet($action, $type, Map $args = null) {
        $args = $args ? : new Map();

        $representer = $this->registry->getActionRepresenter($action);

        try {
            $object = $representer->create($action, $args);

            if (!$representer->hasMissingProperties($object)) {
                $params = ['action' => $action];
                if ($type == CommandResource::TYPE) {
                    $params['do'] = 'post';
                }
                return $this->redirectTo($type, $args, $params);
            }
        } catch (InjectionException $e) {
            $object = $action;
        }

        return [
            'form' => $this->assembleForm($object, $type)
        ];
    }

    private function assembleForm($action, $type) {
        $class = is_object($action) ? get_class($action) : $action;
        if (is_object($action) && $action instanceof PreFilling) {
            $action->preFill($this->dispatcher);
        }

        $representer = $this->registry->getActionRepresenter($class);
        $form = [
            'title' => $representer->getName($class),
            'method' => ($type == QueryResource::TYPE ? 'get' : 'post'),
            'action' => $type,
            'parameter' => [
                ['name' => 'prepared', 'value' => 'true'],
                ['name' => 'action', 'value' => $class],
                ['name' => 'type', 'value' => $type],
                ['name' => 'args[id]', 'value' => $representer->getId($action)],
            ],
            'field' => $this->assembleFields($action, $representer)
        ];
        return $form;
    }

    private function assembleFields($action, ActionRepresenter $representer) {
        return array_map(function (Field $field) {
            return [
                'label' => ucfirst($field->getLabel()),
                'control' => $field->render(),
                'isRequired' => $field->isRequired()
            ];
        }, $representer->getFields($action));
    }
}