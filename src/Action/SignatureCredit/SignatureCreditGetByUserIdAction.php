<?php

namespace App\Action\SignatureCredit;

use App\Domain\SignatureCredit\Service\SignatureCreditService;
use App\Renderer\JsonRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
final class SignatureCreditGetByUserIdAction
{
  private JsonRenderer $renderer;
  private SignatureCreditService $service;

  public function __construct(SignatureCreditService $service, JsonRenderer $jsonRenderer)
  {
    $this->service = $service;
    $this->renderer = $jsonRenderer;
  }

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
  {
    $id = (int)$args['id'];
    $user = $this->service->getByUserId($id);
    return $this->renderer->response($response, $user);
  }

}
