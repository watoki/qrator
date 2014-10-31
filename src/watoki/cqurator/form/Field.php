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

    /**
     * @param string $value
     * @return mixed
     */
    public function inflate($value);

    /**
     * @param bool $to
     * @return void
     */
    public function setRequired($to = true);

    /**
     * @return bool
     */
    public function isRequired();

}