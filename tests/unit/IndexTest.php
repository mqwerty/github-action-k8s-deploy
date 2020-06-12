<?php

/** @noinspection PhpIllegalPsrClassPathInspection PhpUnhandledExceptionInspection */

use App\Action\Index;
use App\App;
use Codeception\Test\Unit;
use Laminas\Diactoros\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class IndexTest extends Unit
{
    protected UnitTester $tester;

    public function testIndex(): void
    {
        new App();
        $action = new Index();
        $result = $action(new ServerRequest());
        static::assertInstanceOf(ResponseInterface::class, $result);
    }
}
