<?php

namespace App;

use App\Action;
use App\Exception\HttpException;
use Laminas\Diactoros\Response;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Spiral\Goridge\StreamRelay;
use Spiral\RoadRunner\PSR7Client;
use Spiral\RoadRunner\Worker;
use Throwable;

final class Router
{
    private ContainerInterface $container;
    private PSR7Client $client;
    private string $env;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->env = $this->container->get('env');
        $this->client = new PSR7Client(new Worker(new StreamRelay(STDIN, STDOUT)));
    }

    public function handle(): void
    {
        try {
            while ($request = $this->client->acceptRequest()) {
                // jit debug, no autostart, see xdebug.ini
                if (isset($xdebugSession)) {
                    $this->client->getWorker()->stop();
                    return;
                }
                if (
                    'prod' !== $this->env
                    && (
                        array_key_exists('XDEBUG_SESSION', $request->getCookieParams())
                        || array_key_exists('XDEBUG_SESSION', $request->getAttributes())
                        || array_key_exists('XDEBUG_SESSION', $request->getQueryParams())
                        || array_key_exists('Xdebug_session', $request->getHeaders())
                    )
                ) {
                    /** @noinspection ForgottenDebugOutputInspection PhpComposerExtensionStubsInspection */
                    xdebug_break();
                    $xdebugSession = true;
                }
                // handle request
                $this->handleRequest($this->client, $request);
            }
        } catch (Throwable $e) {
            $this->client->getWorker()->error((string) $e);
            return;
        }
    }

    /** @noinspection ForgottenDebugOutputInspection */
    private function handleRequest(PSR7Client $psr7, ServerRequestInterface $request): void
    {
        try {
            $response = $this->dispatch($request);
        } catch (HttpException $e) {
            error_log((string) $e);
            $response = 'prod' === $this->env
                ? new Response\JsonResponse(['error' => $e->getMessage()], $e->getCode())
                : self::exToResponce($e);
        } catch (Throwable $e) {
            error_log((string) $e);
            $response = 'prod' === $this->env
                ? new Response\EmptyResponse(500)
                : self::exToResponce($e);
        }
        $psr7->respond($response);
    }

    private function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        return $this->container->get(Action\Index::class)($this->parse($request));
    }

    private function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        if ($request->getBody()->getSize()) {
            try {
                $json = json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
                $request = $request->withParsedBody($json);
            } catch (Throwable $e) {
                throw new HttpException(400, 'Parcing body error', $e);
            }
        }
        return $request;
    }

    private static function exToArray(Throwable $e): array
    {
        return [
            'error' => [
                'class' => get_class($e),
                'msg' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace(),
            ],
        ];
    }

    private static function exToResponce(Throwable $e): Response
    {
        return new Response\JsonResponse(
            self::exToArray($e),
            get_class($e) === HttpException::class ? $e->getCode() : 500,
            [],
            JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );
    }
}
