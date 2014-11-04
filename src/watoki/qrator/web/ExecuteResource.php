<?php
namespace watoki\qrator\web;

use watoki\collections\Map;
use watoki\curir\cookie\Cookie;
use watoki\curir\cookie\CookieStore;
use watoki\curir\protocol\Url;
use watoki\curir\Responder;
use watoki\factory\exception\InjectionException;
use watoki\factory\Factory;
use watoki\qrator\ActionDispatcher;
use watoki\qrator\representer\ActionGenerator;
use watoki\qrator\representer\PropertyActionGenerator;
use watoki\qrator\RepresenterRegistry;
use watoki\tempan\model\ListModel;

class ExecuteResource extends ActionResource {

    const LAST_ACTION_COOKIE = 'lastAction';
    const BREADCRUMB_COOKIE = 'breadcrumbs';

    /** @var \watoki\curir\cookie\CookieStore */
    private $cookies;

    /**
     * @param Factory $factory <-
     * @param RepresenterRegistry $registry <-
     * @param \watoki\curir\cookie\CookieStore $cookies <-
     */
    function __construct(Factory $factory, RepresenterRegistry $registry, CookieStore $cookies) {
        parent::__construct($factory, $registry);
        $this->cookies = $cookies;
    }

    /**
     * @param string $action
     * @param \watoki\collections\Map|null $args
     * @param bool $prepared
     * @return array
     */
    public function doGet($action, Map $args = null, $prepared = false) {
        $args = $args ? : new Map();

        $crumbs = $this->readBreadcrumbs();
        $result = $this->doAction($action, $args, $prepared);

        if ($result instanceof Responder) {
            return $result;
        }

        $representer = $this->registry->getActionRepresenter($action);
        $followUpAction = $representer->getFollowUpAction();

        if ($followUpAction) {
            $url = Url::fromString('execute');
            $url->getParameters()->set('action', $followUpAction->getClass());
            $url->getParameters()->set('args', new Map($followUpAction->getArguments($result)));

            $model = [
                'entity' => null,
                'alert' => "Action executed successfully. Please stand by.",
                'redirect' => ['content' => '3; URL=' . $url->toString()]
            ];
        } else if (!$result && $this->cookies->hasKey(ExecuteResource::LAST_ACTION_COOKIE)) {
            $model = [
                'entity' => null,
                'alert' => "Action executed successfully. You are now redirected to your last action.",
                'redirect' => ['content' => '3; URL=' . $this->urlOfLastAction()->toString()]
            ];
        } else {
            $this->storeLastAction($action, $args);
            $crumbs = $this->updateBreadcrumb($crumbs, $action, $args);

            $entityModel = $this->assembleResult($result);

            if ($entityModel) {
                $model = [
                    'entity' => $entityModel
                ];
            } else {
                $model = [
                    'alert' => "Action executed successfully. Result: " . var_export($result, true)
                ];
            }
        }

        return array_merge([
            'breadcrumbs' => $this->assembleBreadcrumbs($crumbs),
            'entity' => null,
            'alert' => null,
            'redirect' => null
        ], $model);
    }

    private function urlOfLastAction() {
        $lastAction = $this->cookies->read(ExecuteResource::LAST_ACTION_COOKIE)->payload;

        $url = Url::fromString('execute');
        $url->getParameters()->set('action', $lastAction['action']);
        $url->getParameters()->set('args', new Map($lastAction['arguments']));

        return $url;
    }

    /**
     * @param $action
     * @param Map $args
     * @return array
     */
    public function doPost($action, Map $args = null) {
        return $this->doGet($action, $args, true);
    }

    /**
     * @param $action
     * @param Map $args
     * @param $prepared
     * @return \watoki\curir\responder\Redirecter
     */
    private function doAction($action, Map $args, $prepared) {
        $representer = $this->registry->getActionRepresenter($action);

        try {
            $object = $representer->create($args);

            if (!$prepared && $representer->hasMissingProperties($object)) {
                return $this->redirectToPrepare($action, $args);
            }

            return $representer->execute($object);
        } catch (InjectionException $e) {
            return $this->redirectToPrepare($action, $args);
        }
    }

    private function redirectToPrepare($action, Map $args) {
        return $this->redirectTo('prepare', $args, array(
            'action' => $action
        ));
    }

    private function assembleResult($result) {
        if (is_array($result)) {
            return array_map(function ($entity) {
                return $this->assembleEntity($entity);
            }, $result);
        } else if (is_object($result)) {
            return $this->assembleEntity($result);
        } else {
            return null;
        }
    }

    private function assembleEntity($entity) {
        $representer = $this->registry->getEntityRepresenter($entity);
        return [
            'name' => $representer->toString($entity),
            'properties' => new ListModel($this->assembleProperties($entity)),
            'actions' => new ListModel($this->assembleActions($representer->getActions(), $entity)),
        ];
    }

    private function assembleProperties($entity) {
        $properties = [];

        $representer = $this->registry->getEntityRepresenter($entity);
        foreach ($representer->getProperties($entity) as $property) {
            if ($property->canGet()) {
                $properties[] = $this->assembleProperty($entity, $property->name(), $property->get());
            }
        }

        return $properties;
    }

    private function assembleProperty($entity, $name, $value) {
        return [
            'name' => $name,
            'value' => $this->assembleValue($entity, $name, $value)
        ];
    }

    private function assembleValue($entity, $name, $value) {
        if (is_object($value)) {
            $entityRepresenter = $this->registry->getEntityRepresenter($entity);
            $propertyRepresenter = $this->registry->getEntityRepresenter($value);

            return [
                'caption' => $propertyRepresenter->render($value),
                'actions' => array_merge(
                    $this->assembleActions($propertyRepresenter->getActions(), $value),
                    $this->assemblePropertyActions($entityRepresenter->getPropertyActions($name), $value, $entity)
                ),
            ];
        } else if (is_array($value)) {
            array_walk($value, function (&$item) use ($entity, $name) {
                $item = $this->assembleValue($entity, $name, $item);
            });
            return $value;
        }
        return [
            'caption' => $value,
            'actions' => null,
        ];
    }

    private function assemblePropertyActions($actions, $object, $entity) {
        $representer = $this->registry->getEntityRepresenter($entity);
        $id = $representer->getId($entity);

        $propertyRepresenter = $this->registry->getEntityRepresenter($object);
        $propertyId = $propertyRepresenter->getId($object);;

        return array_map(function (PropertyActionGenerator $action) use ($id, $propertyId) {
            return $this->assembleAction($action->getClass(), $action->getArguments($id, $propertyId));
        }, $actions);
    }

    private function assembleActions($actions, $entity) {
        $representer = $this->registry->getEntityRepresenter($entity);
        $id = $representer->getId($entity);

        return array_map(function (ActionGenerator $action) use ($id) {
            return $this->assembleAction($action->getClass(), $action->getArguments($id));
        }, $actions);
    }

    private function assembleAction($action, $arguments) {
        $target = Url::fromString('execute');
        $target->getParameters()->set('action', $action);
        $target->getParameters()->set('args', new Map($arguments));


        $representer = $this->registry->getActionRepresenter($action);
        return [
            'name' => $representer->getName(),
            'link' => [
                'href' => $target->toString()
            ]
        ];
    }

    private function storeLastAction($action, Map $args) {
        $this->cookies->create(new Cookie([
            'action' => $action,
            'arguments' => $args->toArray()
        ]), self::LAST_ACTION_COOKIE);
    }

    private function updateBreadcrumb($crumbs, $action, Map $args) {
        if ($crumbs) {
            $newCrumbs = [];
            foreach ($crumbs as $crumb) {
                /** @noinspection PhpUnusedLocalVariableInspection */
                list($label, $crumbAction, $crumbArgs) = $crumb;
                if ($action == $crumbAction && $args->toArray() == $crumbArgs) {
                    break;
                }
                $newCrumbs[] = $crumb;
            }
            $crumbs = $newCrumbs;
        }

        $representer = $this->registry->getActionRepresenter($action);
        $object = $representer->create($args);
        $caption = $representer->toString($object);

        $crumbs[] = [$caption, $action, $args->toArray()];

        $this->cookies->create(new Cookie($crumbs), self::BREADCRUMB_COOKIE);

        return $crumbs;
    }

    private function readBreadcrumbs() {
        if ($this->cookies->hasKey(self::BREADCRUMB_COOKIE)) {
            return $this->cookies->read(self::BREADCRUMB_COOKIE)->payload;
        }
        return [];
    }

    private function assembleBreadcrumbs($crumbs) {
        $last = array_pop($crumbs);

        return [
            'breadcrumb' => array_map(function ($crumb) {
                list($caption, $action, $args) = $crumb;
                $url = Url::fromString('execute');
                $url->getParameters()->set('action', $action);
                $url->getParameters()->set('args', new Map($args));
                return [
                    'caption' => $caption,
                    'link' => ['href' => $url->toString()]
                ];
            }, $crumbs),
            'current' => $last[0]
        ];
    }
}