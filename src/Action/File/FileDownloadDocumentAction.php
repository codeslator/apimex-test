<?php

namespace App\Action\File;

use App\Domain\File\Service\MediaService;
use App\Renderer\JsonRenderer;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class FileDownloadDocumentAction
{
  private JsonRenderer $renderer;

  private MediaService $service;

  public function __construct(MediaService $service, JsonRenderer $renderer)
  {
    $this->service = $service;
    $this->renderer = $renderer;
  }

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
  {
    
    $filePath = $this->service->downloadDocumentFile($args);
    return $this->renderer->response($response, ['file' => $filePath])->withStatus(StatusCodeInterface::STATUS_OK);
  }
}
