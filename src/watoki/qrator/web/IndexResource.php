<?php
namespace watoki\qrator\web;

use watoki\curir\Container;
use watoki\curir\responder\Redirecter;
use watoki\qrator\RootAction;

class IndexResource extends Container {

    public function doGet() {
        return Redirecter::fromString('execute?action=' . RootAction::class);
    }
}