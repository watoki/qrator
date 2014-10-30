<?php
namespace watoki\cqurator\web;

use watoki\collections\Map;
use watoki\cqurator\form\Field;
use watoki\cqurator\form\PreFilling;
use watoki\cqurator\Representer;

class PrepareResource extends ActionResource {

    protected function redirectToPrepare(Map $args, $action, $type) {
        throw new \LogicException('Cannot redirect. Already at prepare.');
    }

    /**
     * @param string $action
     * @param string $type
     * @param null|\watoki\collections\Map $args
     * @return array
     */
    public function doGet($action, $type, Map $args = null) {
        $object = $this->createAction($action);
        try {
            $this->prepareAction($args, $object);
            return $this->redirectTo($type, $args, array('action' => $action));
        } catch (\UnderflowException $e) {
            // That's why we are here
        }

        return [
            'form' => $this->assembleForm($object, $type)
        ];
    }

    private function assembleForm($action, $type) {
        if ($action instanceof PreFilling) {
            $action->preFill($this->dispatcher);
        }

        $representer = $this->registry->getRepresenter(get_class($action));
        $form = [
            'title' => $representer->toString($action),
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

    private function assembleFields($action, Representer $representer) {
        return array_map(function (Field $field) {
            return [
                'label' => ucfirst($field->getLabel()),
                'control' => $field->render()
            ];
        }, $representer->getFields($action));
    }
}