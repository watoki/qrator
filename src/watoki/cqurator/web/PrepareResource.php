<?php
namespace watoki\cqurator\web;

use watoki\deli\Request;

class PrepareResource extends ActionResource {

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
            return $this->redirectTo('', $request, $action, $type);
        } catch (\UnderflowException $e) {
            // That's why we are here
        }
        return [];
    }

    protected function redirectToPrepare(Request $request, $action, $type) {
        throw new \LogicException('Cannot redirect. Already at prepare.');
    }

    /**
     * @return string
     */
    protected function getType() {
    }

    private function redirectToAction($request, $action, $type) {
    }
}