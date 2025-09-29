<?php

namespace App\Domain\Mail\Service;

use App\Factory\LoggerFactory;
use Psr\Log\LoggerInterface;
use Mailgun\Mailgun;
use SendGrid;
use Brevo\Client\Configuration;
use Brevo\Client\Api\TransactionalEmailsApi;
use GuzzleHttp\Client;
use Brevo\Client\Model\SendSmtpEmail;

final class MailService
{
  private $mailService;
  private string $templatePath = __DIR__ . '/../../../../templates/mail';
  private LoggerInterface $logger;

  public function __construct(
    LoggerFactory $loggerFactory
  ) {
    $this->logger = $loggerFactory
      ->addFileHandler('mail_log.log')
      ->createLogger();
    // Determina qué servicio de correo usar en base a la variable de entorno
    if ($_SERVER['MAIL_SERVICE'] === 'MAILGUN') {
      $this->mailService = $this->setupMailgun();
    } elseif ($_SERVER['MAIL_SERVICE'] === 'SENDGRID') {
      $this->mailService = $this->setupSendgrid();
    } elseif ($_SERVER['MAIL_SERVICE'] === 'BREVO') {
      $this->mailService = $this->setupBrevo();
    } else {
      throw new \Exception('No valid mail service configured');
    }
  }


  private function setupMailgun()
  {
    // Usamos la nueva forma de crear el cliente en Mailgun 4.x
    return Mailgun::create($_SERVER['MAILGUN_API_KEY']);
  }

  private function setupSendgrid()
  {
    // Creamos el cliente de SendGrid
    return new SendGrid($_SERVER['SENDGRID_API_KEY']);
  }

  private function setupBrevo()
  {
    // Creamos el cliente de Brevo
    $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $_SERVER['BREVO_API_KEY']);
    return new TransactionalEmailsApi(
      new Client(),
      $config
    );
  }

  public function send(string $to, string $subject, string $template, array $data, array $files = [])
  {
    $from = $_SERVER['SUPPORT_EMAIL'];
    $htmlContent = $this->renderTemplate($template, $data);
    if ($_SERVER['MAIL_SERVICE'] === 'MAILGUN') {
      $this->sendMailgun($from, $to, $subject, $htmlContent, $files);
    } elseif ($_SERVER['MAIL_SERVICE'] === 'SENDGRID') {
      $this->sendSendgrid($from, $to, $subject, $htmlContent, $files);
    } elseif ($_SERVER['MAIL_SERVICE'] === 'BREVO') {
      $this->sendBrevo($from, $to, $subject, $htmlContent, $files);
    }
  }

  private function sendMailgun($from, $to, $subject, $html, $files)
  {
    $fromEmail = "FirmaVirtual México <$from>";
    $params = [
      'from'    => $fromEmail,
      'to'      => $to,
      'subject' => $subject,
      'html'    => $html,
    ];

    if (!empty($files)) {
      $params['attachment'] = $files;
    }

    $this->mailService->messages()->send($_SERVER['MAILGUN_DOMAIN_AUTH'], $params);
  }

  private function sendSendgrid($from, $to, $subject, $html, $files)
  {
    $email = new SendGrid\Mail\Mail();
    $email->setFrom($from, 'FirmaVirtual México');
    $email->setSubject($subject);
    $email->addTo($to);
    $email->addContent("text/html", $html);

    // Agregar archivos adjuntos si los hay
    if (!empty($files)) {
      foreach ($files as $file) {
        $filePath = $file['filePath'];
        $fileName = $file['filename'];
        $fileContent = base64_encode(file_get_contents($filePath));
        $email->addAttachment($fileContent, 'application/octet-stream', $fileName);
      }
    }

    try {
      $response = $this->mailService->send($email);
      $this->logger->info('Email sent with SendGrid', ['statusCode' => $response->statusCode()]);
    } catch (\Exception $e) {
      $this->logger->error('SendGrid error: ' . $e->getMessage());
      throw $e;
    }
  }

  private function sendBrevo($from, $to, $subject, $html, $files)
  {
    $attachments = [];
    if (!empty($files)) {
      foreach ($files as $file) {
        $attachment = [
          'url' => $file['filePath'],
          'name' => $file['filename']
        ];
        $attachments[] = $attachment;
      }
    }
    $sendSmtpEmail = new SendSmtpEmail([
      'sender' => [
        'name' => 'FirmaVirtual México',
        'email' => $from
      ],
      'to' => [
        [
          'name' => $to,
          'email' => $to,
        ]
      ],
      'subject' => $subject,
      'htmlContent' => $html,
      'attachment' => !empty($attachments) ? $attachments : null
    ]);

    try {
      $this->mailService->sendTransacEmail($sendSmtpEmail);
    } catch (\Exception $e) {
      $this->logger->error('Brevo error: ' . $e->getMessage());
      throw $e;
    }
  }

  public function renderTemplate(string $template, array $data): string
  {
    $templates = new \League\Plates\Engine($this->templatePath);
    $template  = $templates->render($template, $data);
    return (string)$template;
  }
}
