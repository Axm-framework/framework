<?php

namespace Axm;

use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

class HandlerErrors
{
    public static function make(PrettyPageHandler $handler, Run $run)
    {
        $run->pushHandler($handler);
        $handler->setEditor(env('DEBUGBAR_EDITOR'));

        set_exception_handler(function ($exception) use ($run) {
            $run->handleException($exception);
        });
    }
}
