<?php
namespace watoki\qrator;

use watoki\factory\Factory;
use watoki\factory\Injector;
use watoki\qrator\Representer;
use watoki\qrator\representer\ActionLink;
use watoki\qrator\representer\generic\GenericActionRepresenter;
use watoki\qrator\representer\generic\GenericEntityRepresenter;
use watoki\qrator\representer\MethodActionRepresenter;

class RepresenterRegistry {

    /** @var array|Representer[] */
    private $representers = [];

    /** @var Factory */
    private $factory;

    /**
     * @param Factory $factory <-
     */
    public function __construct(Factory $factory) {
        $this->factory = $factory;
        $factory->setSingleton(get_class($this), $this);

        $this->register((new GenericActionRepresenter(RootAction::class, $factory))
            ->setHandler(function () {
                return new RootEntity();
            })
            ->setName('Home'));

        $this->register((new GenericEntityRepresenter(RootEntity::class))
            ->setName('Qrator'));

        $this->register((new GenericEntityRepresenter(\DateTime::class))
            ->setStringifier(function (\DateTime $d) {
                return $d->format('Y-m-d H:i:s');
            }));
    }

    /**
     * @param string $class
     * @return bool
     */
    public function isRegistered($class) {
        return array_key_exists($class, $this->representers);
    }

    /**
     * @param Representer $representer
     * @return \watoki\qrator\Representer
     */
    public function register(Representer $representer) {
        $this->representers[$representer->getClass()] = $representer;
        return $representer;
    }

    /**
     * @param string $class Full name of the class
     * @param string $method Name of the method
     * @return MethodActionRepresenter
     */
    public function registerActionMethod($class, $method) {
        return $this->register(new MethodActionRepresenter($class, $method, $this->factory));
    }

    /**
     * @param $class
     * @param array $methodActions Of method names, indexed by their classes
     * @throws \Exception
     * @return GenericEntityRepresenter
     */
    public function registerEntityMethodActions($class, $methodActions) {
        $representer = new GenericEntityRepresenter($class);
        $this->register($representer);

        $defaultCallback = function () {
            return true;
        };

        $actions = [];
        foreach ($methodActions as $class => $methods) {
            if ($methods instanceof ActionRepresenter) {
                $this->register($methods);
                $actions[$methods->getClass()] = $defaultCallback;
            } else {
                foreach ($methods as $i => $method) {
                    $callback = $defaultCallback;
                    if (is_callable($method)) {
                        $callback = $method;
                        $method = $i;
                    }

                    $actionRepresenter = $this->registerActionMethod($class, $method);
                    $actions[$actionRepresenter->getClass()] = $callback;

                    try {
                        $method = new \ReflectionMethod($class, '__' . $method);
                        if (!$method->isStatic()) {
                            throw new \Exception("Method [$class::__$method] must be static");
                        }
                        $injector = new Injector($this->factory);
                        $method->invokeArgs(null, $injector->injectMethodArguments($method, [$actionRepresenter]));
                    } catch (\ReflectionException $e) {
                    }
                }
            }
        }

        $representer->setActions(function ($entity) use ($actions) {
            $activeActions = [];
            foreach ($actions as $class => $callback) {
                if ($callback($entity)) {
                    if (isset($entity->id)) {
                        $args = ['id' => $entity->id];
                    } else if (method_exists($entity, 'getId')) {
                        $args = ['id' => $entity->getId()];
                    } else {
                        $args = [];
                    }
                    $activeActions[] = new ActionLink($class, $args);
                }
            }
            return $activeActions;
        });

        return $representer;
    }

    /**
     * @param string|object|null $class
     * @throws \Exception
     * @return EntityRepresenter
     */
    public function getEntityRepresenter($class) {
        return $this->getRepresenter($class, EntityRepresenter::class, function ($class) {
            return new GenericEntityRepresenter($class);
        });
    }

    /**
     * @param string|object|null $class
     * @throws \Exception
     * @return ActionRepresenter
     */
    public function getActionRepresenter($class) {
        return $this->getRepresenter($class, ActionRepresenter::class, function ($class) {
            return new GenericActionRepresenter($class, $this->factory);
        });
    }

    private function getRepresenter($class, $interface, $defaultGenerator) {
        if (is_object($class)) {
            $class = get_class($class);
        }

        if (!isset($this->representers[$class])) {
            $this->representers[$class] = $defaultGenerator($class);
        }

        $representer = $this->representers[$class];
        if (!is_subclass_of($representer, $interface)) {
            throw new \Exception("Class [" . get_class($representer) . "] needs to implement [" . $interface . "].");
        }
        return $representer;
    }
}