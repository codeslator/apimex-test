<?php

namespace App\Domain\Client\Service;

use App\Domain\ClientContact\Data\ClientContactItem;
use App\Domain\ClientContact\Repository\ClientContactRepository;
use App\Domain\ClientContact\Utilities\ClientContactValidator;
use App\Domain\ClientContact\Utilities\ClientContactUtils;
use Ramsey\Uuid\Uuid;
use App\Factory\LoggerFactory;
use Psr\Log\LoggerInterface;

class ClientContactService
{
  private ClientContactRepository $repository;
  private ClientContactValidator $validator;
  private ClientContactUtils $utils;
  private LoggerInterface $logger;

  public function __construct(
    ClientContactRepository $repository,
    ClientContactValidator $validator,
    ClientContactUtils $utils,
    LoggerFactory $loggerFactory
  )
  {
    $this->repository = $repository;
    $this->validator = $validator;
    $this->utils = $utils;
    $this->logger = $loggerFactory
      ->addFileHandler('clients_contact.log')
      ->createLogger();
  }

  public function create(array $data): void
  {
    try {
      $this->repository->pdo->beginTransaction();
      $this->validator->validate($data);
      $contact = new ClientContactItem();
      $contact->uuid = Uuid::uuid4()->toString();
      $contact->rfc = $data['rfc'];
      $contact->curp = $data['curp'] ?? null;
      $contact->first_name = $this->utils->capitalize($data['first_name']);
      $contact->last_name = $this->utils->capitalize($data['last_name']);
      $contact->mother_last_name = $this->utils->capitalize($data['mother_last_name']);
      $contact->email = $data['email'];
      $contact->phone = $data['phone'] ?? null;
      $id = (int) $this->repository->save($contact);
      $this->logger->info("Contact created with ID [{$id}]. Contact email: {$contact->email}");
      $this->repository->pdo->commit();
    } catch (\Exception $e) {
      $this->repository->pdo->rollBack();
      throw new \Exception($e->getMessage());
    }
  }

  public function getAll(array $pagination): array
  {
    $data = $this->repository->getAll($pagination);
    if (count($data['data']) > 0) {
      $rows = [];
      foreach ($data['data'] as $contact) {
        $rows[] = $this->utils->transform($contact);
      }
      $data['data'] = $rows;
    }
    return $data;
  }


  public function getById(int $id): ClientContactItem
  {
    return $this->utils->transform($this->repository->getById($id));
  }
}
