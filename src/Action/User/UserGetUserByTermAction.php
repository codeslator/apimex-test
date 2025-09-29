<?php

namespace App\Action\User;

use App\Domain\User\Service\UserService;
use App\Renderer\JsonRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Fig\Http\Message\StatusCodeInterface;

final class UserGetUserByTermAction
{
  private JsonRenderer $renderer;
  private UserService $service;

  public function __construct(UserService $service, JsonRenderer $jsonRenderer)
  {
    $this->service = $service;
    $this->renderer = $jsonRenderer;
  }

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
  {
    $pagination = $request->getQueryParams();
    $users = $this->service->getUserByTerm($pagination);
    return $this->renderer->json($response, $users)->withStatus(StatusCodeInterface::STATUS_OK);
  }
}
