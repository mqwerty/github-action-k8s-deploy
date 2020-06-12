<?php

namespace App\Action;

use Laminas\Diactoros\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class Index
{
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        return new Response\JsonResponse(['result' => 'test']);
    }
}
