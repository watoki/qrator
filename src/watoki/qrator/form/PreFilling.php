<?php
namespace watoki\qrator\form;

use watoki\qrator\ActionRepresenter;
use watoki\smokey\Dispatcher;

interface PreFilling {

    /**
     * @param ActionRepresenter $representer
     * @return void
     */
    public function preFill(ActionRepresenter $representer);

} 