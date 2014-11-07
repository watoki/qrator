<?php
namespace spec\watoki\qrator\fixtures;

use watoki\qrator\representer\ActionLink;
use watoki\qrator\representer\generic\GenericActionRepresenter;
use watoki\qrator\representer\generic\GenericEntityRepresenter;
use watoki\qrator\representer\Property;
use watoki\qrator\RepresenterRegistry;
use watoki\scrut\Fixture;

/**
 * @property ClassFixture class <-
 */
class RegistryFixture extends Fixture {

    /** @var RepresenterRegistry */
    public $registry;

    /** @var GenericActionRepresenter[]|\watoki\qrator\representer\generic\GenericEntityRepresenter[] */
    public $representers = array();

    private $actions = [];

    private $propertyActions = [];

    public function setUp() {
        parent::setUp();
        $this->registry = new RepresenterRegistry($this->spec->factory);
    }

    public function givenIRegisteredAnActionRepresenterFor($class) {
        if (array_key_exists($class, $this->representers)) {
            return;
        }
        $this->representers[$class] = new GenericActionRepresenter($class, $this->spec->factory, $this->registry);
        $this->registry->register($this->representers[$class]);
    }

    public function givenIRegisteredAnEntityRepresenterFor($class) {
        if (array_key_exists($class, $this->representers)) {
            return;
        }
        $this->representers[$class] = new GenericEntityRepresenter($class);
        $this->registry->register($this->representers[$class]);
    }

    public function givenIAddedTheAction_ToTheRepresenterOf($action, $class) {
        $this->class->givenTheClass_WithTheBody($action, 'public $id;');

        $this->actions[$class][] = $action;
        $this->representers[$class]->setActions(function ($entity) use ($class) {
            return array_map(function ($action) use ($entity) {
                return new ActionLink($action, isset($entity->id) ? ['id' => $entity->id] : null);
            }, $this->actions[$class]);
        });
    }

    public function givenIHaveTheTheRenderer_For($callable, $class) {
        $this->representers[$class]->setRenderer($callable);
    }

    public function givenIAddedAnAction_ForTheProperty_Of($action, $property, $class) {
        $this->propertyActions[$class][$property][] = $action;
        $this->representers[$class]->setPropertyAction($property, function ($entity, Property $propertyObject) use ($property, $class) {
            return array_map(function ($action) use ($entity, $propertyObject) {
                return new ActionLink($action, ['id' => $entity->id, 'object' => $propertyObject->get($entity)->id]);
            }, $this->propertyActions[$class][$property]);
        });
    }

    public function givenIHaveSetFor_ThePrefiller($action, $callback) {
        $this->representers[$action]->setPreFiller($callback);
    }

    public function givenIHaveSet_AsTheListActionFor($action, $entity) {
        $this->representers[$entity]->setListAction(new $action);
    }

} 