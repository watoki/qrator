<?php
namespace watoki\cqurator\web;

use watoki\cqurator\form\PreFilling;
use watoki\cqurator\Representer;
use watoki\cqurator\form\Field;
use watoki\deli\Request;

class PrepareResource extends ActionResource {

    protected function redirectToPrepare(Request $request, $action, $type) {
        throw new \LogicException('Cannot redirect. Already at prepare.');
    }

    /**
     * @param Request $request <-
     * @param string $action
     * @param string $type
     * @return array
     */
    public function doGet(Request $request, $action, $type) {
        $object = $this->createAction($action);
        try {
            $this->prepareAction($request, $object);
            return $this->redirectTo($type, $request, array('action' => $action));
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