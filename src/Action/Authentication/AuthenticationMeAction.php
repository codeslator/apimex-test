<?php

namespace App\Action\Authentication;

use App\Domain\Permission\Service\PermissionService;
use App\Renderer\JsonRenderer;
use App\Domain\SignatureCredit\Service\SignatureCreditService;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AuthenticationMeAction
{
  private JsonRenderer $renderer;
  private PermissionService $permissionService;
  private SignatureCreditService $signatureCreditService;

  public function __construct(
    PermissionService $permissionService,
    SignatureCreditService $signatureCreditService,
    JsonRenderer $renderer,
  )
  {
    $this->permissionService = $permissionService;
    $this->signatureCreditService = $signatureCreditService;
    $this->renderer = $renderer;
  }

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
  {
    $user = $request->getAttribute('user');
    $client = $request->getAttribute('client');
    $identity = $request->getAttribute('auth_identity');
    $usage = $request->getAttribute('usage');

    if (!$user) {
      return $this->renderer->response($response, [
        'error' => 'No authenticated user found.'
      ])->withStatus(StatusCodeInterface::STATUS_UNAUTHORIZED);
    }

    $permissions = $this->permissionService->getPermissionsByRole($user->role->code);
    $credits = $this->signatureCreditService->getByUserId($user->id);

    $signatureCredits = null;
    if ($user->signature_credit && $credits) {
      $signatureCredits = [
        'total' => $credits->consumed_quantity + $credits->remaining_quantity,
        'remaining' => $credits->remaining_quantity,
        'used' => $credits->consumed_quantity,
      ];
    }

    $data = [
      'auth_identity' => $identity,
      'user' => $user,
      'signature_credit' => $signatureCredits,
      'role' => $user->role,
      'permissions' => $permissions,
    ];

    if ($client) {
      $data['client'] = $client;
      $data['usage'] = $usage;
    }

    unset($data['user']->signature_credit);
    unset($data['user']->role);
    unset($data['client']->user);
    unset($data['client']->api_keys);

    return $this->renderer->response($response, $data)->withStatus(StatusCodeInterface::STATUS_OK);
  }
}
