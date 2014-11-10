<?php
namespace watoki\qrator\web;

use watoki\collections\Map;
use watoki\curir\cookie\Cookie;
use watoki\curir\cookie\CookieStore;
use watoki\curir\protocol\Url;
use watoki\curir\Responder;
use watoki\dom\Element;
use watoki\factory\exception\InjectionException;
use watoki\factory\Factory;
use watoki\qrator\representer\ActionLink;
use watoki\qrator\representer\Property;
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
        $args = $args ?: new Map();

        $crumbs = $this->readBreadcrumbs();
        $result = $this->doAction($action, $args, $prepared);

        if ($result instanceof Responder) {
            return $result;
        }

        $representer = $this->registry->getActionRepresenter($action);
        $followUpAction = $representer->getFollowUpAction($result);

        if ($followUpAction) {
            $url = Url::fromString('execute');
            $url->getParameters()->set('action', $followUpAction->getClass());
            $url->getParameters()->set('args', $followUpAction->getArguments());

            $model = [
                'entity' => null,
                'alert' => "Action executed successfully. Please stand by.",
                'redirect' => ['content' => '1; URL=' . $url->toString()]
            ];
        } else if (is_null($result) && $this->cookies->hasKey(ExecuteResource::LAST_ACTION_COOKIE)) {
            $model = [
                'entity' => null,
                'alert' => "Action executed successfully. You are now redirected to your last action.",
                'redirect' => ['content' => '1; URL=' . $this->urlOfLastAction()->toString()]
            ];
        } else {
            $this->storeLastAction($action, $args);
            $crumbs = $this->updateBreadcrumb($crumbs, $action, $args);

            $entityModel = $this->assembleResult($result);
            $noShow = count($entityModel) > 1 ? 'list' : 'table';;

            if ($entityModel) {
                $model = [
                    'entity' => $entityModel,
                    'properties' => $entityModel[0]['properties'],
                    $noShow => ['class' => function (Element $e) {
                        return $e->getAttribute('class')->getValue() . ' no-show';
                    }]
                ];
            } else {
                if ($result) {
                    $resultString = "Result: " . var_export($result, true);
                } else {
                    $resultString = 'Empty result.';
                }
                $model = [
                    'alert' => "Action executed successfully. " . $resultString
                ];
            }
        }

        return array_merge([
            'breadcrumbs' => $this->assembleBreadcrumbs($crumbs),
            'entity' => null,
            'properties' => null,
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
     * @throws \watoki\curir\error\HttpError
     * @return \watoki\curir\responder\Redirecter
     */
    private function doAction($action, Map $args, $prepared) {
        $representer = $this->registry->getActionRepresenter($action);

        try {
            $object = $representer->create($args);
        } catch (InjectionException $e) {
            return $this->redirectToPrepare($action, $args);
        }

        if (!$prepared && $representer->hasMissingProperties($object)) {
            return $this->redirectToPrepare($action, $args);
        }

        return $representer->execute($object);
    }

    private function redirectToPrepare($action, Map $args) {
        return $this->redirectTo('prepare', $args, array(
            'action' => $action
        ));
    }

    private function assembleResult($result) {
        if ($this->isArray($result)) {
            $entities = [];
            foreach ($result as $entity) {
                $entities[] = $this->assembleEntity($entity, true);
            }
            return $entities;
        } else if (is_object($result)) {
            return [$this->assembleEntity($result)];
        } else {
            return null;
        }
    }

    private function assembleEntity($entity, $short = false) {
        $representer = $this->registry->getEntityRepresenter($entity);
        $properties = $this->assembleProperties($entity, $short);
        $actions = $this->assembleActions($representer->getActions($entity), $entity);
        return [
            'name' => $representer->toString($entity),
            'properties' => new ListModel($properties),
            'actions' => new ListModel($actions),
            'property' => $properties,
            'action' => $actions,
        ];
    }

    private function assembleProperties($entity, $short) {
        $properties = [];

        $representer = $this->registry->getEntityRepresenter($entity);
        $entityProperties = $short ? $representer->getCondensedProperties($entity) : $representer->getProperties($entity);
        foreach ($entityProperties as $property) {
            if ($property->canGet($entity)) {
                $properties[] = $this->assembleProperty($entity, $property);
            }
        }

        return $properties;
    }

    private function assembleProperty($entity, Property $property) {
        return [
            'name' => $property->name(),
            'label' => ucfirst(preg_replace('/([a-z])([A-Z])/', '$1 $2', $property->name())),
            'value' => $this->assembleValue($entity, $property)
        ];
    }

    private function assembleValue($entity, Property $property) {
        $value = $property->get($entity);

        if ($this->isArray($value)) {
            $values = [];
            foreach ($value as $item) {
                $values[] = $this->assembleValueWithActions($entity, $property, $item);
            }
            return $values;
        } else {
            return $this->assembleValueWithActions($entity, $property, $value);
        }
    }

    private function assembleValueWithActions($entity, Property $property, $value) {
        $entityRepresenter = $this->registry->getEntityRepresenter($entity);
        if (is_object($value)) {
            $propertyRepresenter = $this->registry->getEntityRepresenter($value);

            return [
                'caption' => $propertyRepresenter->render($value),
                'actions' => array_merge(
                    $this->assembleActions($propertyRepresenter->getActions($value)),
                    $this->assembleActions($entityRepresenter->getPropertyActions($entity, $property->name(), $value))
                ),
            ];
        }

        return [
            'caption' => $this->toString($value),
            'actions' => null,
        ];
    }

    private function toString($value) {
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }
        return print_r($value, true);
    }

    /**
     * @param $actions
     * @return array|\watoki\qrator\representer\ActionLink[]
     */
    private function assembleActions($actions) {
        return array_map(function ($action) {
            return $this->assembleAction($action);
        }, $actions);
    }

    private function assembleAction(ActionLink $action) {
        $target = Url::fromString('execute');

        $target->getParameters()->set('action', $action->getClass());
        $target->getParameters()->set('args', $action->getArguments());

        $representer = $this->registry->getActionRepresenter($action->getClass());
        return [
            'caption' => $representer->render(),
            'link' => [
                'href' => $target->toString(),
                'onclick' => $representer->requiresConfirmation()
                    ? "return confirm('" . $representer->requiresConfirmation() . "');"
                    : 'return true;'
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

    private function isArray($var) {
        return is_array($var) || $var instanceof \ArrayAccess;
    }
}