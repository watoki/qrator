<?php
namespace watoki\cqurator\representer;

use watoki\collections\Map;

class ActionGenerator {

    private $class;

    protected $argumentGenerator;

    /**
     * @param string $class
     * @param null|callable $argumentGenerator Takes entity ID and return array with action arguments
     */
    public function __construct($class, $argumentGenerator = null) {
        $this->argumentGenerator = $argumentGenerator ? : function ($id) {
            if (!$id) {
                return [];
            }
            return [
                'id' => $id
            ];
        };
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