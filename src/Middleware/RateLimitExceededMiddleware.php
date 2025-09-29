<?php

namespace App\Middleware;

use DateTimeImmutable;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\RateLimiter\Exception\RateLimitExceededException;

final class RateLimitExceededMiddleware implements MiddlewareInterface
{
    private ResponseFactoryInterface $responseFactory;

    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        try {
            return $handler->handle($request);
        } catch (RateLimitExceededException $exception) {
            return $this->transform($exception);
        }
    }

    private function transform(RateLimitExceededException $exception): ResponseInterface
    {
        $response = $this->responseFactory->createResponse(429);

        $seconds = $exception->getRetryAfter()->getTimestamp() -
            (new DateTimeImmutable('now'))->getTimestamp();

        $response = $response->withHeader('Retry-After', (string)$seconds);

        $json = (string)json_encode(['error' => ['message' => 'Too Many Requests']]);
        $response->getBody()->write($json);

        $response = $response
            ->withHeader('X-RateLimit-Remaining', $exception->getRemainingTokens())
            ->withHeader('X-RateLimit-Retry-After', $exception->getRetryAfter()->getTimestamp())
            ->withHeader('X-RateLimit-Limit', $exception->getLimit());

        return $response->withHeader('Content-Type', 'application/json');
    }
}
