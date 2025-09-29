<?php

namespace App\Action\Statistic;

use App\Domain\Statistic\Service\StatisticService;
use App\Renderer\JsonRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
final class StatisticGetAllTotalsAction
{
  private JsonRenderer $renderer;
  private StatisticService $service;

  public function __construct(StatisticService $service, JsonRenderer $jsonRenderer)
  {
    $this->service = $service;
    $this->renderer = $jsonRenderer;
  }

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
  {
    $statistics = $this->service->getAllTotals();
    return $this->renderer->response($response, $statistics);
  }
}
