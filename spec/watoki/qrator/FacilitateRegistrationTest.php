<?php
namespace spec\watoki\qrator;

use watoki\qrator\Registrar;
use watoki\qrator\RepresenterRegistry;
use watoki\scrut\Specification;

/**
 * @property \spec\watoki\reflect\fixtures\ClassFixture class <-
 */
class FacilitateRegistrationTest extends Specification {

    /** @var Registrar */
    private $registrar;

    /** @var RepresenterRegistry */
    public $registry;

    protected function background() {
        $this->class->givenTheClass_WithTheBody('SomeActionHandler', '
            function someAction() {}
            function otherAction() {}
        ');
        $this->registry = new RepresenterRegistry($this->factory);
        $this->registrar = new Registrar(\DateTime::class, $this->registry, $this->factory);
    }

    function testRegisterEntityRepresenter() {
        $this->assertSame($this->registrar->getRepresenter(), $this->registry->getEntityRepresenter(\DateTime::class));
    }

    function testRegisterMethodAction() {
        $someAction = $this->registrar->addMethodAction('SomeActionHandler', 'someAction');
        $otherAction = $this->registrar->addMethodAction('SomeActionHandler', 'otherAction');

        $this->assertSame($someAction, $this->registry->getActionRepresenter($someAction->getClass()));
        $actions = $this->registrar->getRepresenter()->getActions(new \DateTime());

        $this->assertCount(2, $actions);
        $this->assertEquals($someAction->getClass(), $actions[0]->getClass());
        $this->assertEquals($otherAction->getClass(), $actions[1]->getClass());
    }

    function testControlVisibleActionsWithPredicate() {
        $ifInFuture = function (\DateTime $d) {
            return $d > new \DateTime();
        };
        $ifInPast = function (\DateTime $d) {
            return $d < new \DateTime();
        };

        $someAction = $this->registrar->addMethodAction('SomeActionHandler', 'someAction', $ifInFuture);
        $otherAction = $this->registrar->addMethodAction('SomeActionHandler', 'otherAction', $ifInPast);

        $actions = $this->registrar->getRepresenter()->getActions(new \DateTime('1 hour ago'));
        $this->assertCount(1, $actions);
        $this->assertEquals($otherAction->getClass(), $actions[0]->getClass());

        $actions = $this->registrar->getRepresenter()->getActions(new \DateTime('1 hour'));
        $this->assertCount(1, $actions);
        $this->assertEquals($someAction->getClass(), $actions[0]->getClass());
    }

    function testGenerateArguments() {
        $this->registrar->addMethodAction('SomeActionHandler', 'someAction', null, function (\DateTime $d) {
            return ['time' => $d->format('Y-m-d')];
        });

        $actions = $this->registrar->getRepresenter()->getActions(new \DateTime('2001-02-03'));
        $this->assertEquals(['time' => '2001-02-03'], $actions[0]->getArguments()->toArray());
    }

    function testDefaultArgumentsFromProperty() {
        $this->class->givenTheClass_WithTheBody('EntityWithIdentifierProperty',
            'public $id = 73;');

        $this->registrar->addMethodAction('SomeActionHandler', 'someAction');

        /** @noinspection PhpUndefinedClassInspection */
        $actions = $this->registrar->getRepresenter()->getActions(new \EntityWithIdentifierProperty);
        $this->assertEquals(['id' => 73], $actions[0]->getArguments()->toArray());
    }

    function testDefaultArgumentsFromGetter() {
        $this->class->givenTheClass_WithTheBody('EntityWithIdentifierGetter',
            'public function getId() { return 42; }');

        $this->registrar->addMethodAction('SomeActionHandler', 'someAction');

        /** @noinspection PhpUndefinedClassInspection */
        $actions = $this->registrar->getRepresenter()->getActions(new \EntityWithIdentifierGetter);
        $this->assertEquals(['id' => 42], $actions[0]->getArguments()->toArray());
    }

} 