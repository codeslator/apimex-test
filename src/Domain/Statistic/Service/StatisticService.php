<?php

namespace App\Domain\Statistic\Service;

use App\Domain\Statistic\Data\AdminTotalsStatistic;
use App\Domain\Statistic\Repository\StatisticRepository;
use DomainException;

final class StatisticService
{
  private StatisticRepository $repository;

  public function __construct(
    StatisticRepository $repository,
  ) {
    $this->repository = $repository;
  }

  public function getAllTotals(): AdminTotalsStatistic
  {
    $data = $this->repository->getAllTotals();
    $statistic = new AdminTotalsStatistic();
    $statistic->total_documents = (int) $data['total_documents'];
    $statistic->total_users = (int) $data['total_users'];
    $statistic->total_signatures = (int) $data['total_signatures'];
    $statistic->total_payments = (int) $data['total_payments'];
    $statistic->total_revenue = (float) $data['total_revenue'];
    $statistic->total_active_clients = (int) $data['total_active_clients'];
    $statistic->total_active_client_api_keys = (int) $data['total_active_client_api_keys'];
    $statistic->total_api_requests = (int) $data['total_api_requests'];
    return $statistic;
  }
}