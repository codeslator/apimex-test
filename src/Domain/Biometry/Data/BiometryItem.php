<?php
namespace App\Domain\Biometry\Data;

final class BiometryItem {
  public int $id;
  public int $signer_id;
  public int $document_id;
  public string $verification_code;
  public ?bool $has_photo_identity_uploaded;
  public ?bool $has_biometric_identity_uploaded;
  public ?bool $has_video_identity_uploaded;
  public ?string $session_id;
  public ?string $scan_id;
  public bool $is_done;
  public ?string $validation_url;
  public ?bool $is_url_active;
  public ?string $current_step;
  public ?string $completed_at;
  public string $created_at;
}