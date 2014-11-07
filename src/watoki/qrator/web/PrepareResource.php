<?php
namespace watoki\qrator\web;

use watoki\collections\Map;
use watoki\dom\Element;
use watoki\dom\Text;
use watoki\factory\exception\InjectionException;
use watoki\qrator\form\Field;
use watoki\qrator\form\fields\HiddenField;
use watoki\qrator\Representer;

class PrepareResource extends ActionResource {

    /** @var array|string[] */
    private $head = [];

    /** @var array|string[] */
    private $foot = [];

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
        }

        return [
            'form' => $this->assembleForm($action, $args),
            'head' => function (Element $element) {
                    $element->getChildren()->append(new Text(implode("\n", array_unique($this->head))));
                    return true;
                },
            'foot' => implode("\n", array_unique($this->foot))
        ];
    }

    private function assembleForm($action, Map $args) {
        $representer = $this->registry->getActionRepresenter($action);

        $parameters = [
            ['name' => 'action', 'value' => $representer->getClass()],
        ];

        $fields = $representer->getFields();

        foreach ($fields as $field) {
            $this->head[] = $field->requireHead();
            $this->foot[] = $field->requireFoot();
        }

        $this->fill($fields, $args);
        $representer->preFill($fields);

        $form = [
            'title' => $representer->getName(),
            'action' => 'execute',
            'parameter' => $parameters,
            'field' => $this->assembleFields($fields)
        ];
        return $form;
    }

    /**
     * @param Field[] $fields
     * @param Map $args
     */
    private function fill($fields, Map $args) {
        foreach ($args as $key => $value) {
            if (array_key_exists($key, $fields)) {
                $fields[$key]->setValue($value);
            }
        }
    }

    private function assembleFields($fields) {
        return array_map(function (Field $field) {
            return [
                'label' => ($field instanceof HiddenField) ? null : ucfirst($field->getLabel()),
                'control' => $field->render(),
                'isRequired' => $field->isRequired()
            ];
        }, array_values($fields));
    }
}