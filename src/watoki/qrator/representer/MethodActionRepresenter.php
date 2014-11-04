<?php
namespace watoki\qrator\representer;

use watoki\collections\Map;
use watoki\factory\Factory;
use watoki\factory\providers\CallbackProvider;
use watoki\qrator\representer\generic\GenericActionRepresenter;
use watoki\qrator\representer\property\PublicProperty;
use watoki\qrator\RepresenterRegistry;

class MethodActionRepresenter extends GenericActionRepresenter {

    /** @var \ReflectionMethod */
    private $method;

    /**
     * @param string $className
     * @param string $methodName
     * @param Factory $factory <-
     * @param RepresenterRegistry $registry <-
     */
    public function __construct($className, $methodName, Factory $factory, RepresenterRegistry $registry) {
        parent::__construct(self::asClass($className, $methodName), $factory, $registry);

        $this->method = new \ReflectionMethod($className, $methodName);
        $this->createClassDefinition();
    }

    public function execute($object) {
        $handler = $this->factory->getInstance($this->method->getDeclaringClass()->getName());
        $properties = $this->getProperties($object);

        $args = [];
        foreach ($this->method->getParameters() as $parameter) {
            $args[] = $properties[$parameter->getName()]->get($object);
        }
        return $this->method->invokeArgs($handler, $args);
    }

    public static function asClass($class, $method) {
        return $class . '__' . $method;
    }

    /**
     * @return string
     */
    public function getName() {
        return ucfirst(preg_replace('/([a-z])([A-Z])/', '$1 $2', $this->method->getShortName()));
    }

    private function createClassDefinition() {
        $fullClassName = $this->getClass();
        if (class_exists($fullClassName)) {
            return;
        }

        $parts = explode('\\', $fullClassName);
        $shortName = array_pop($parts);
        $namespace = implode('\\', $parts);

        $properties = [];
        $parameters = [];
        $body = [];
        $getters = [];

        foreach ($this->method->getParameters() as $parameter) {
            $name = $parameter->getName();
            $parameters[] = '$' . $name
                . ($parameter->isDefaultValueAvailable() ? ' = ' . var_export($parameter->getDefaultValue(), true) : '');
            $body[] = '$this->' . $name . ' = $' . $name . ';';
            $properties[] = 'private $' . $name . ';';

            $hint = null;
            $matches = [];
            if ($parameter->getClass()) {
                $hint = $parameter->getClass()->getName();
            } else if (preg_match('/@param\s+(\S+)\s+\$' . $name . '/', $this->method->getDocComment(), $matches)) {
                $hint = $matches[1];
            }
            $getters[] = ($hint ? "/**\n   * @return " . $hint . "\n   */\n  " : '')
                . 'function get' . ucfirst($name) . '() { return $this->' . $name . '; }';
        }

        $code = ($namespace ? "namespace $namespace;\n" : '') . "class $shortName {\n"
            . '  ' . implode("\n  ", $properties) . "\n"
            . '  function __construct(' . implode(', ', $parameters) . ") {\n"
            . '    ' . implode("\n    ", $body) . "\n"
            . '  }' . "\n"
            . '  ' . implode("\n  ", $getters) . "\n"
            . '}';
        eval($code);
    }
}