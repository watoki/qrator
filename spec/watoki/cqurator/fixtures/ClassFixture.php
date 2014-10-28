<?php
namespace spec\watoki\cqurator\fixtures;

use watoki\scrut\Fixture;

class ClassFixture extends Fixture {

    public function givenTheClass($fqn) {
        $parts = explode('\\', $fqn);
        $name = array_pop($parts);
        $namespace = implode('\\', $parts);

        $code = "namespace $namespace; class $name {}";
        $evald = eval($code);
        if (!$evald === false) {
            throw new \Exception("Could not eval: \n\n" . $code);
        }
    }
}