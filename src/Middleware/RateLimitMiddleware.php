<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final class RateLimitMiddleware implements MiddlewareInterface
{
    private RateLimiterFactory $rateLimiterFactory;

    public function __construct(RateLimiterFactory $rateLimiterFactory)
    {
        $this->rateLimiterFactory = $rateLimiterFactory;
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // create a limiter based on a unique identifier of the client
        // e.g. the client's IP address, a username/email, an API key, etc.
        $authIdentity = $request->getAttribute('auth_identity');

        if (!$authIdentity || !isset($authIdentity['id'])) {
            // Si no hay identidad, aplicamos un rate limit genérico (opcional)
            $key = 'anonymous';
        } else {
            // Construimos clave única: tipo + id
            $key = $authIdentity['type'] . '_' . $authIdentity['id'];
        }

        // Creamos el limiter para esta identidad
        $limiter = $this->rateLimiterFactory->create($key);

        // Consumimos un token (puede lanzar RateLimitExceededException)
        $limit = $limiter->consume(1);

        if (!$limit->isAccepted()) {
            throw new \Symfony\Component\RateLimiter\Exception\RateLimitExceededException($limit);
        }

        // Agregamos info del limit en el request (útil para logging o métricas)
        $request = $request->withAttribute('rate_limit', $limit);

        // Si pasa, continúa
        return $handler->handle($request);
    }
}
