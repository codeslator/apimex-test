<?php

namespace App\Domain\Mail\Service;

use App\Domain\Customer\Repository\CustomerRepository;
use App\Factory\LoggerFactory;
use Psr\Log\LoggerInterface;
use Mailgun\Mailgun;

final class SendMail
{
    private $mailgun;
    private LoggerInterface $logger;

    public function __construct(
        LoggerFactory $loggerFactory
    ) {
        $this->mailgun = Mailgun::create($_SERVER['MAILGUN_API_KEY'], $_SERVER['MAILGUN_DOMAIN_API']);
        $this->logger = $loggerFactory
            ->addFileHandler('SendMail.log')
            ->createLogger();
    }

    public function send(string $to, string $subject, string $template, array $data)
    {
        $params = array(
            'from'      => 'FirmaVirtual <' . $_SERVER['SUPPORT_EMAIL'] . '>',
            'to'        => $to,
            'subject'   => $subject,
            'html'      => $this->renderTemplate($template, $data)
        );

        // # Make the call to the client.
        $this->mailgun->messages()->send($_SERVER['MAILGUN_DOMAIN_AUTH'], $params);
    }

    public function renderTemplate(string $template, array $data): string
    {
        $templates = new \League\Plates\Engine(__DIR__ . '/../../../../templates/mail');
        $template  = $templates->render($template, $data);
        return ((string)$template);
    }
}
