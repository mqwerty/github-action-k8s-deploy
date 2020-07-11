<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace Unit;

use App\Action\Index;
use Codeception\Test\Unit;
use Laminas\Diactoros\ServerRequest;
use UnitTester;

class IndexTest extends Unit
{
    protected UnitTester $tester;

    public function testIndex(): void
    {
        $action = new Index();
        $result = $action(new ServerRequest());
        static::assertEquals(200, $result->getStatusCode());
    }
}
