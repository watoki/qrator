<?php
namespace watoki\cqurator\representer;

use watoki\cqurator\contracts\Representer;

class GenericRepresenter implements Representer {

    /** @var array|\blog\curator\contracts\Query[] */
    private $queries = array();

    public function addQuery($query) {
        $this->queries[] = $query;
    }

    public function getQueries() {
        return $this->queries;
    }
}