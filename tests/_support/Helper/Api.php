<?php

namespace Helper;

use Codeception\Module;

class Api extends Module
{
    public function _initialize(): void
    {
        /** @noinspection PhpIncludeInspection */
        require_once codecept_root_dir() . '/vendor/autoload.php';
    }
}
