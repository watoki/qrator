<?php
namespace watoki\qrator\web;

use watoki\collections\Map;
use watoki\curir\Container;
use watoki\curir\cookie\Cookie;
use watoki\curir\cookie\CookieStore;
use watoki\curir\delivery\WebResponse;
use watoki\curir\error\HttpError;
use watoki\curir\protocol\Url;
use watoki\curir\rendering\adapter\TempanRenderer;
use watoki\curir\Responder;
use watoki\curir\responder\Redirecter;
use watoki\deli\Request;
use watoki\dom\Element;
use watoki\dom\Text;
use watoki\factory\exception\InjectionException;
use watoki\factory\Factory;
use watoki\qrator\form\Field;
use watoki\qrator\representer\ActionLink;
use watoki\qrator\RepresenterRegistry;
use watoki\qrator\RootAction;
use watoki\reflect\Property;
use watoki\tempan\model\ListModel;

class ExecuteResource extends Container {

    const LAST_ACTION_COOKIE = 'lastAction';
    const BREADCRUMB_COOKIE = 'breadcrumbs';

    /** @var RepresenterRegistry */
    protected $registry;

    /** @var \watoki\curir\cookie\CookieStore */
    private $cookies;

    /** @var array|string[] */
    private $head = [];

    /** @var array|string[] */
    private $foot = [];

    /**
     * @param Factory $factory <-
     * @param RepresenterRegistry $registry <-
     * @param \watoki\curir\cookie\CookieStore $cookies <-
     */
    function __construct(Factory $factory, RepresenterRegistry $registry, CookieStore $cookies) {
        parent::__construct($factory);
        $this->registry = $registry;
        $this->cookies = $cookies;
    }

    protected function createDefaultRenderer() {
        return new TempanRenderer();
    }

    public function respond(Request $request) {
        try {
            return parent::respond($request);
        } catch (\Exception $e) {
            throw new HttpError(WebResponse::STATUS_SERVER_ERROR, $e->getMessage(), null, 0, $e);
        }
    }

    /**
     * @param $action
     * @param Map|null $args
     * @return array
     */
    public function doPost($action, Map $args = null) {
        return $this->doGet($action, $args);
    }

    /**
     * @param string $action defaults to RootAction
     * @param \watoki\collections\Map|null $args
     * @throws \watoki\curir\error\HttpError
     * @return array
     */
    public function doGet($action = RootAction::class, Map $args = null) {
        $args = $args ? : new Map();
        $representer = $this->registry->getActionRepresenter($action);

        try {
            $object = $representer->create($args);
        } catch (InjectionException $e) {
            $object = null;
        }

        $model = [];
        $crumbs = $this->readBreadcrumbs();
        if ($object) {
            try {
                $result = $representer->execute($object);

                if ($result instanceof Responder || $result instanceof WebResponse) {
                    $this->updateBreadcrumb($crumbs, $object, $args);
                    return $result;
                }

                $followUpAction = $representer->getFollowUpAction($result);

                if ($followUpAction) {
                    return new Redirecter($this->urlOfAction($followUpAction));
                } else if (is_null($result) && $this->cookies->hasKey(ExecuteResource::LAST_ACTION_COOKIE)) {
                    return new Redirecter($this->urlOfLastAction());
                } else {
                    $this->storeLastAction($action, $args);
                    $crumbs = $this->updateBreadcrumb($crumbs, $object, $args);
                    $model = $this->assemblePossiblyEmptyResult($result);
                }
            } catch (\Exception $e) {
                $details = '';
                $currentException = $e;
                while ($currentException) {
                    $details .= $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString() . "\n\n";
                    $currentException = $currentException->getPrevious();
                }
                $model = [
                    'error' => [
                        'message' => $e->getMessage(),
                        'details' => $details
                    ],
                    'isPreparing' => false
                ];
            }
        }

        return array_merge([
            'breadcrumbs' => $this->assembleBreadcrumbs($crumbs),
            'entity' => null,
            'properties' => null,
            'alert' => null,
            'error' => null,
            'title' => $representer->getName(),
            'isPreparing' => true,
            'form' => $this->assembleForm($action, $args),
            'head' => function (Element $element) {
                    $element->getChildren()->append(new Text(implode("\n", array_unique($this->head))));
                    return true;
                },
            'foot' => implode("\n", array_unique($this->foot))
        ], $model);
    }

    protected function assemblePossiblyEmptyResult($result) {
        $entityModel = $this->assembleResult($result);

        if ($entityModel) {
            $keys = array_keys($entityModel);
            $noShow = $keys[0] !== 0 ? 'table' : 'list';

            return [
                'entity' => $entityModel,
                'isPreparing' => false,
                'properties' => $keys[0] !== 0 ? $entityModel['properties'] : $entityModel[0]['properties'],
                $noShow => ['class' => function (Element $e) {
                        return $e->getAttribute('class')->getValue() . ' no-show';
                    }],
            ];
        } else {
            return [
                'alert' => $result ? "Result: " . var_export($result, true) : 'Empty result.',
                'isPreparing' => false,
            ];
        }
    }

    protected function assembleForm($action, Map $args) {
        $representer = $this->registry->getActionRepresenter($action);

        $parameters = [
            ['name' => 'action', 'value' => $representer->getClass()],
        ];

        $fields = $representer->getFields();

        if (!$fields) {
            return null;
        }

        foreach ($fields as $field) {
            $this->head = array_merge($this->head, $field->addToHead());
            $this->foot = array_merge($this->foot, $field->addToFoot());
        }

        $this->fill($fields, $args);
        $representer->preFill($fields);

        $form = [
            'action' => $representer->getResourceUrl()->toString(),
            'parameter' => $parameters,
            'field' => $this->assembleFields($fields)
        ];
        return $form;
    }

    /**
     * @param Field[] $fields
     * @param Map $args
     */
    protected function fill($fields, Map $args) {
        foreach ($args as $key => $value) {
            if (array_key_exists($key, $fields)) {
                $fields[$key]->setValue($value);
            }
        }
    }

    protected function assembleFields($fields) {
        return array_map(function (Field $field) {
            return $field->render();
        }, array_values($fields));
    }

    protected function urlOfLastAction() {
        $lastAction = $this->cookies->read(ExecuteResource::LAST_ACTION_COOKIE)->payload;

        $representer = $this->registry->getActionRepresenter($lastAction['action']);

        return $representer->getResourceUrl()
            ->withParameter('action', $lastAction['action'])
            ->withParameter('args', new Map($lastAction['arguments']));
    }

    protected function assembleResult($result) {
        if ($this->isArray($result)) {
            $entities = [];
            foreach ($result as $entity) {
                $entities[] = $this->assembleEntity($entity, true);
            }
            return $entities;
        } else if (is_object($result)) {
            return $this->assembleEntity($result);
        } else {
            return null;
        }
    }

    protected function assembleEntity($entity, $short = false) {
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

    protected function assembleProperties($entity, $short) {
        $properties = [];

        $representer = $this->registry->getEntityRepresenter($entity);
        $entityProperties = $short
            ? $representer->getCondensedProperties($entity)
            : $representer->getProperties($entity);

        foreach ($entityProperties as $property) {
            if ($property->canGet($entity)) {
                $properties[] = $this->assembleProperty($entity, $property);
            }
        }

        return $properties;
    }

    protected function assembleProperty($entity, Property $property) {
        return [
            'name' => $property->name(),
            'label' => ucfirst(preg_replace('/([a-z])([A-Z])/', '$1 $2', $property->name())),
            'value' => $this->assembleValue($entity, $property)
        ];
    }

    protected function assembleValue($entity, Property $property) {
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

    protected function assembleValueWithActions($entity, Property $property, $value) {
        if (is_object($value)) {
            $entityRepresenter = $this->registry->getEntityRepresenter($entity);
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
    protected function assembleActions($actions) {
        return array_map(function ($action) {
            return $this->assembleAction($action);
        }, $actions);
    }

    protected function assembleAction(ActionLink $action) {
        $representer = $this->registry->getActionRepresenter($action->getClass());

        $target = Url::fromString($representer->getResourceUrl())
            ->withParameter('action', $action->getClass())
            ->withParameter('args', $action->getArguments());

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

    protected function storeLastAction($action, Map $args) {
        $this->cookies->create(new Cookie([
            'action' => $action,
            'arguments' => $args->toArray()
        ]), self::LAST_ACTION_COOKIE);
    }

    protected function updateBreadcrumb($crumbs, $object, Map $args) {
        if ($crumbs) {
            $newCrumbs = [];
            foreach ($crumbs as $crumb) {
                /** @noinspection PhpUnusedLocalVariableInspection */
                list($label, $crumbAction, $crumbArgs) = $crumb;
                if (get_class($object) == $crumbAction && $args->toArray() == $crumbArgs) {
                    break;
                }
                $newCrumbs[] = $crumb;
            }
            $crumbs = $newCrumbs;
        }

        $representer = $this->registry->getActionRepresenter($object);
        $caption = $representer->toString($object);

        $crumbs[] = [$caption, $representer->getClass(), $args->toArray()];

        $this->saveBreadCrumbs($crumbs);

        return $crumbs;
    }

    protected function saveBreadCrumbs($crumbs) {
        $this->cookies->create(new Cookie($crumbs), self::BREADCRUMB_COOKIE);
    }

    protected function readBreadcrumbs() {
        if ($this->cookies->hasKey(self::BREADCRUMB_COOKIE)) {
            return $this->cookies->read(self::BREADCRUMB_COOKIE)->payload;
        }
        return [];
    }

    protected function assembleBreadcrumbs($crumbs) {
        return [
            'breadcrumb' => array_map(function ($crumb) {
                list($caption, $action, $args) = $crumb;
                $representer = $this->registry->getActionRepresenter($action);

                $url = $representer->getResourceUrl()
                    ->withParameter('action', $action)
                    ->withParameter('args', new Map($args));
                return [
                    'caption' => $caption,
                    'link' => ['href' => $url->toString()]
                ];
            }, $crumbs)
        ];
    }

    protected function isArray($var) {
        return is_array($var) || $var instanceof \ArrayAccess;
    }

    protected function urlOfAction(ActionLink $followUpAction) {
        $representer = $this->registry->getActionRepresenter($followUpAction->getClass());

        return $representer->getResourceUrl()
            ->withParameter('action', $followUpAction->getClass())
            ->withParameter('args', $followUpAction->getArguments());
    }

}