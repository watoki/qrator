<?php
namespace watoki\cqurator\representer;

use watoki\cqurator\ActionRepresenter;
use watoki\cqurator\form\Field;
use watoki\cqurator\form\StringField;

class GenericActionRepresenter extends GenericRepresenter implements ActionRepresenter {

    /** @var array|Field[] */
    private $fields = [];

    /**
     * @param object $object
     * @return array|\watoki\cqurator\form\Field[]
     */
    public function getFields($object) {
        $fields = [];
        foreach ($this->getProperties($object) as $property) {
            if (!$property->canSet() || $property->name == 'id') {
                continue;
            }

            $field = $this->getField($property->name);
            $fields[] = $field;

            if ($property->canGet()) {
                $field->setValue($property->get());
            }
        }
        return $fields;
    }

    /**
     * @param $name
     * @return Field
     */
    public function getField($name) {
        if (isset($this->fields[$name])) {
            return $this->fields[$name];
        }
        return new StringField($name);
    }

    /**
     * @param string $name
     * @param Field $field
     */
    public function setField($name, Field $field) {
        $this->fields[$name] = $field;
    }

} 