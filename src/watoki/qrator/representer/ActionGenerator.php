<?php
namespace watoki\qrator\representer;

use watoki\collections\Map;

class ActionGenerator {

    private $class;

    protected $argumentGenerator;

    /**
     * @param string $class
     * @param null|array|callable $arguments Takes entity ID and return array with action arguments
     */
    public function __construct($class, $arguments = null) {
        if (is_array($arguments)) {
            $this->argumentGenerator = function () use ($arguments) {
                return $arguments;
            };
        } else if ($arguments) {
            $this->argumentGenerator = $arguments;
        } else {
            $this->argumentGenerator = function ($id) {
                if (!$id) {
                    return [];
                }
                return [
                    'id' => $id
                ];
            };
        }
        $this->class = $class;
    }

    public function getClass() {
        return $this->class;
    }

    /**
     * @param mixed $id
     * @return Map
     */
    public function getArguments($id) {
        return call_user_func($this->argumentGenerator, $id);
    }
}