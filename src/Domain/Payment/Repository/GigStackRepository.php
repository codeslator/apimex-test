<?php

namespace App\Domain\Payment\Repository;

use App\Factory\CurlFactory;
use DomainException;

final class GigStackRepository
{
  private $curlFactory;

  public function __construct()
  {
    $this->curlFactory = new CurlFactory('https://gigstack-cfdi-bjekv7t4.uc.gateway.dev/', ["Authorization: " . $_SERVER['TOKEN_GIGSTACK']]);
  }

  public function generatePaymentLink(array $data)
  {
    try {
      $requesApi = $this->curlFactory->request('v1/payments/create', 'POST', $data);
      $requesApiDecode = json_decode($requesApi);
      if (isset($requesApiDecode->code)) {
        throw new DomainException(sprintf("$requesApiDecode->code $requesApiDecode->message"));
      }

      return $requesApiDecode;
    } catch (\Exception $e) {
      throw new DomainException('Error en ejecucion del curl gigstack ' . sprintf($e->getMessage()));
    }
  }

  public function verifyLogin(string $userId)
  {
    $data = [
      "user_id" => $userId,
      "auth_type" => "auth"
    ];

    try {
      $requesApi = $this->curlFactory->request('/users/auth_user_data', 'POST', $data);
      $requesApiDecode = json_decode($requesApi);
      // print_r($requesApiDecode);

      if (isset($requesApiDecode->error)) {
        throw new DomainException(sprintf($requesApiDecode->message, ''));
      }
      return $requesApiDecode;
    } catch (\Exception $e) {
      throw new DomainException(sprintf($e->getMessage(), ''));
    }
  }

  /**
   * 
   * 
   * @param array $data
   * $data['full_name'] => Client Fullname;
   * $data['email'] => Client E-mail;
   * $data['item_name'] => Name of item to pay;
   * $data['item_price'] => Price of item to pay;
   * $data['item_tax'] => Tax of item to pay;
   * $data['document_id'] => Document id to add in metadata;
   * $data['signer_id'] => Signature id to add in metadata;
   * $data['package_id'] => Package id to add in metadata;
   * @return mixed $invoice JSON Data
   */
  public function generateInvoice(array $data): mixed
  {
    $invoice = [
      'automateInvoiceOnComplete' => true,
      'client' => [
        "name" => $data['full_name'],
        "email" => $data['email']
      ],
      "items" => [
        [
          "name" =>  $data['item_name'],
          "description" => "",
          "quantity" => 1,
          "total" => $data['item_price'],
          "taxes" => [
            [
              "rate" => $data['item_tax'],
              "factor" => "Tasa",
              "withholding" => false,
              "type" => "IVA"
            ]
          ]
        ]
      ],
      "currency" => "MXN",
      "methodsTypesOptions" => ["card"],
      "metadata" => [
        "document_id" => $data['document_id'] ?? null,
        "signer_id" => $data['signer_id'] ?? null,
        "package_id" => $data['package_id'] ?? null,
      ]
    ];
    return $invoice;
  }
}
