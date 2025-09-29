<?php

namespace App\Factory;

use Error;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class DopplerFactory
{
  private Client $client;
  private string $dopplerApiKey;
  private string $dopplerProject;
  private string $dopplerConfig;

  public function __construct(Client $client, string $dopplerApiKey, string $dopplerProject, string $dopplerConfig)
  {
    $this->client = $client;
    $this->dopplerApiKey = $dopplerApiKey;
    $this->dopplerProject = $dopplerProject;
    $this->dopplerConfig = $dopplerConfig;
  }

  public function fetchEnvironmentVariables(): array
  {
    $url = sprintf('https://api.doppler.com/v3/configs/config/secrets/download?project=%s&config=%s&format=json', $this->dopplerProject, $this->dopplerConfig);

    try {
      $response = $this->client->request('GET', $url, [
        'headers' => [
          'Authorization' => "Bearer $this->dopplerApiKey",
        ],
      ]);

      $data = json_decode($response->getBody()->getContents(), true);
      return $data ?? [];
    } catch (RequestException $e) {
      throw new Error($e->getMessage());
    }
  }
}
