<?php 

namespace App\Domain\ClientApiKeyUsage\Data;

final class ClientApiKeyUsageData {
    public int $id;
    public int $client_api_key_id;
    public int $request_count;
    public ?string $first_request_at;
    public ?string $last_request_at;
    public string $window_start;
}