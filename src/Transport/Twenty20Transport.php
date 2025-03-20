<?php

namespace Twenty20\Mailer\Transport;

use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Email;
use Twenty20\Mailer\Mailer;

class Twenty20Transport extends AbstractTransport
{
    protected Mailer $mailer;

    public function __construct(Mailer $mailer, ?LoggerInterface $logger = null)
    {
        parent::__construct($logger);
        $this->mailer = $mailer;
    }

    protected function doSend(SentMessage $message): void
    {
        $email = $message->getOriginalMessage();

        if (! $email instanceof Email) {
            throw new \RuntimeException('Unsupported message type.');
        }

        $from = $this->formatSendGridFrom($email->getFrom());
        $to = $email->getTo()[0]->getAddress();

        $subject = $email->getSubject();
        $ccEmail = $email->getCc() ? array_map(fn($email) => $email->getAddress(), $email->getCc()) : [];
        $bccEmail = $email->getBcc() ? array_map(fn($email) => $email->getAddress(), $email->getBcc()) : [];
        $replyToEmail = $email->getReplyTo() ? array_map(fn($email) => $email->getAddress(), $email->getReplyTo()) : [];
        $body = $email->getHtmlBody() ?? $email->getTextBody();
        $headers = $email->getHeaders();
        $metadata = $headers->toArray();

        $newArray = array_slice($metadata, 3);

        $this->mailer->sendMail($to, $from, $subject, $body, $ccEmail, $bccEmail, $replyToEmail, $newArray);
    }

    private function formatSendGridFrom(array $addresses): array
    {
        if (empty($addresses)) {
            throw new \RuntimeException('From address is required.');
        }

        $address = $addresses[0]; // Only one From address allowed

        return [
            'email' => (string) $address->getAddress(),
            'name' => (string) ($address->getName() ?: ''),
        ];
    }

    public function __toString(): string
    {
        return 'twenty20-mailer';
    }
}
