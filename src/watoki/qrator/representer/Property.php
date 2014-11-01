<?php
namespace watoki\qrator\representer;

interface Property {

    public function name();

    public function isRequired();

    public function canGet();

    public function canSet();

    public function get();

    public function set($value);

} 