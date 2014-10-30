<?php
namespace watoki\cqurator\web;

use watoki\cqurator\contracts\Representer;
use watoki\cqurator\form\Field;
use watoki\cqurator\RepresenterRegistry;
use watoki\deli\Request;
use watoki\factory\Factory;

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

    public function doPost(Request $request, $action, $type) {
        return $this->doGet($request, $action, $type);
    }

    private function assembleForm($action, $type) {
        $representer = $this->registry->getRepresenter(get_class($action));
        $form = [
            'title' => $representer->toString($action),
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