<?php

namespace App\Middleware;

use App\Domain\Permission\Service\PermissionService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Routing\RouteContext;
use App\Factory\LoggerFactory;
use Psr\Log\LoggerInterface;
use App\Domain\ClientApiKey\Service\ClientApiKeyService;
use App\Domain\Authentication\Service\JwtService;
use App\Domain\ClientApiKeyUsage\Service\ClientApiKeyUsageService;

final class AuthMiddleware
{
  private ResponseFactoryInterface $responseFactory;
  private LoggerInterface $logger;
  private ClientApiKeyService $apiKeyService;
  private JwtService $jwtService;
  private PermissionService $permissionService;
  private ClientApiKeyUsageService $apiKeyUsageService;

  public function __construct(
    ResponseFactoryInterface $responseFactory,
    LoggerFactory $loggerFactory,
    ClientApiKeyService $apiKeyService,
    JwtService $jwtService,
    PermissionService $permissionService,
    ClientApiKeyUsageService $apiKeyUsageService
  ) {
    $this->responseFactory = $responseFactory;
    $this->apiKeyService = $apiKeyService;
    $this->jwtService = $jwtService;
    $this->permissionService = $permissionService;
    $this->apiKeyUsageService = $apiKeyUsageService;
    $this->logger = $loggerFactory
      ->addFileHandler('authentication.log')
      ->createLogger();
  }

  public function __invoke(
    ServerRequestInterface $request,
    RequestHandlerInterface $handler
  ): ResponseInterface {

    $routeContext = RouteContext::fromRequest($request);
    $route = $routeContext->getRoute();

    if (!$route) {
      return $this->errorMessage(404, 'Route not found.');
    }

    $this->logger->info(sprintf('Route to Access: %s', $route->getPattern()));
    $permissionCode = $route->getName(); // Ejemplo: LIST_ROLES
    $this->logger->info(sprintf('Route Name (Permission): %s', $permissionCode));

    // 1. JWT AUTHENTICATION
    $jwtHeader = $request->getHeaderLine('Authorization');
    if (!empty($jwtHeader) && preg_match('/Bearer\s(\S+)/', $jwtHeader, $matches)) {
      $token = $matches[1];
      try {
        $decoded = $this->jwtService->validate($token);
        $user = (object) $decoded['user'];

        if (!$this->permissionService->hasPermission($user->role->code, $permissionCode)) {
          return $this->errorMessage(403, 'Access denied: Insufficient permissions.');
        }

        $this->logger->info("JWT auth OK: {$user->email} | Role: {$user->role->code}");
        $this->logger->info(sprintf('Authentication User: %s', $user->email));
        $this->logger->info(sprintf('Authentication Role: %s', $user->role->code));
        $request = $request->withAttribute('user', $user)
          ->withAttribute('auth_identity', [
            'type' => 'jwt',
            'id' => $user->id,
            'token' => $token,
          ]);
        return $handler->handle($request);
      } catch (\Exception $e) {
        return $this->errorMessage(401, $e->getMessage());
      }
    }

    // 2. API KEY AUTHENTICATION
    $apiKeyHeader = $request->getHeaderLine('X-API-Key');
    if (!empty($apiKeyHeader)) {
      try {
        $apikey = $this->apiKeyService->validateKey($apiKeyHeader);

        // Registrar el uso de la API Key
        $maxRequests = 1000;
        $windowSeconds = 3600;
        if ($apikey->api_key->rate_limit !== null) {
          $maxRequests = $apikey->api_key->rate_limit;
        }
        if ($apikey->api_key->rate_limit_window !== null) {
          $windowSeconds = $apikey->api_key->rate_limit_window;
        }
        try {
          $this->apiKeyUsageService->logUsage($apikey->api_key->id, $maxRequests, $windowSeconds);
        } catch (\DomainException $e) {
          return $this->errorMessage(429, 'Rate limit exceeded. Try again later.');
        }

        if (!$this->permissionService->hasPermission($apikey->role->code, $permissionCode)) {
          return $this->errorMessage(403, 'Access denied: Insufficient permissions.');
        }

        $this->logger->info("API Key auth OK: {$apikey->client->name} | Role: {$apikey->role->code}");
        $this->logger->info(sprintf('Authentication User: %s', $apikey->client->user->email));
        $this->logger->info(sprintf('Authentication Role: %s', $apikey->role->code));

        $request = $request
          ->withAttribute('client', $apikey->client)
          ->withAttribute('user', $apikey->client->user)
          ->withAttribute('usage', $apikey->usage)
          ->withAttribute('auth_identity', [
            'type' => 'api_key',
            'id' => $apikey->client->id,
            'apikey' => $apiKeyHeader
          ]);
        return $handler->handle($request);
      } catch (\DomainException $e) {
        return $this->errorMessage(401, $e->getMessage());
      } catch (\Exception $e) {
        return $this->errorMessage(500, sprintf('Unexpected error validating API Key: %s', $e->getMessage()));
      }
    }

    // 3. Ningún método presente
    return $this->errorMessage(401, 'Authorization header or X-API-Key is required.');
  }

  /**
   * Generates an error response with a JSON body.
   */
  private function errorMessage(int $code, string $message): ResponseInterface
  {
    $response = $this->responseFactory->createResponse($code);
    $response->getBody()->write(json_encode([
      'error' => [
        'code' => $code,
        'message' => $message,
      ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    return $response->withStatus($code)->withHeader('Content-Type', 'application/json');
  }
}
