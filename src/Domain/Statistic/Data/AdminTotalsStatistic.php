<?php
namespace App\Domain\Statistic\Data;

final class AdminTotalsStatistic {
  public int $total_documents;
  public int $total_users;
  public int $total_signatures;
  public int $total_payments;
  public int $total_active_clients;
  public int $total_active_client_api_keys;
  public int $total_api_requests;
  public float $total_revenue;
}