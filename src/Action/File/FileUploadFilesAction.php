<?php

namespace App\Action\File;

use App\Domain\File\Service\MediaService;
use App\Renderer\JsonRenderer;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class FileUploadFilesAction
{
  private JsonRenderer $renderer;

  private MediaService $service;

  public function __construct(MediaService $service, JsonRenderer $renderer)
  {
    $this->service = $service;
    $this->renderer = $renderer;
  }

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
  {
    $data = (array)$request->getParsedBody();
    $userAgent = (string)$request->getHeaderLine('User-Agent');
    $this->service->uploadValidationFiles($data, $userAgent);
    return $this->renderer->json($response, ['message' => 'Files uploaded successfully.'])->withStatus(StatusCodeInterface::STATUS_CREATED);
  }
}
