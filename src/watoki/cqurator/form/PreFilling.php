<?php
namespace watoki\cqurator\form;

use watoki\smokey\Dispatcher;

interface PreFilling {

    /**
     * @param Dispatcher $dispatcher
     * @return void
     */
    public function preFill(Dispatcher $dispatcher);

} 