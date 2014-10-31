<?php
namespace watoki\cqurator\web;

use watoki\collections\Map;
use watoki\cqurator\ActionRepresenter;
use watoki\cqurator\form\Field;
use watoki\cqurator\form\PreFilling;
use watoki\cqurator\Representer;

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
        $object = $representer->create($action, $args);

        if (!$representer->hasMissingProperties($object)) {
            $params = ['action' => $action];
            if ($type == CommandResource::TYPE) {
                $params['do'] = 'post';
            }
            return $this->redirectTo($type, $args, $params);
        }

        return [
            'form' => $this->assembleForm($object, $type)
        ];
    }

    private function assembleForm($action, $type) {
        if ($action instanceof PreFilling) {
            $action->preFill($this->dispatcher);
        }

        $representer = $this->registry->getActionRepresenter($action);
        $form = [
            'title' => $representer->getName(get_class($action)),
            'method' => ($type == QueryResource::TYPE ? 'get' : 'post'),
            'action' => $type,
            'parameter' => [
                ['name' => 'action', 'value' => get_class($action)],
                ['name' => 'type', 'value' => $type],
                ['name' => 'args[id]', 'value' => $representer->getId($action)]
            ],
            'field' => $this->assembleFields($action, $representer)
        ];
        return $form;
    }

    private function assembleFields($action, ActionRepresenter $representer) {
        return array_map(function (Field $field) {
            return [
                'label' => ucfirst($field->getLabel()),
                'control' => $field->render()
            ];
        }, $representer->getFields($action));
    }
}