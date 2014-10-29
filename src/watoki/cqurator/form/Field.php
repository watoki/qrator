<?php
namespace watoki\cqurator\form;

interface Field {

    /**
     * @return string
     */
    public function getLabel();

    /**
     * @return string
     */
    public function render();

    /**
     * @param mixed $value
     */
    public function setValue($value);
}