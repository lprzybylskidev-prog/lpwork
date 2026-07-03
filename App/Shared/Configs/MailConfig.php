<?php

declare(strict_types=1);

namespace App\Shared\Configs;

use LPWork\Config\Contracts\ConfigDefinition;
use LPWork\Environment\Environment;

/**
 * Configures default mail transport, sender identities, templates, and mail logging.
 */
final class MailConfig implements ConfigDefinition
{
    public function key(): string
    {
        return 'mail';
    }

    /**
     * @return array<array-key, mixed>
     */
    public function values(): array
    {
        $transport = Environment::get('MAIL_TRANSPORT', 'log');

        return [
            // MAIL_TRANSPORT supports log, smtp, sendmail, ses, and mailgun.
            'default' => $transport,
            'default_sender' => Environment::get('MAIL_FROM', 'app'),
            'template_view' => null,
            // Named senders can be referenced by application mail messages.
            'senders' => [
                'app' => [
                    'address' => Environment::get('MAIL_FROM_ADDRESS', 'hello@example.test'),
                    'name' => Environment::get('MAIL_FROM_NAME', 'LPWork App'),
                ],
                'support' => [
                    'address' => Environment::get('MAIL_SUPPORT_ADDRESS', 'support@example.test'),
                    'name' => Environment::get('MAIL_SUPPORT_NAME', 'LPWork Support'),
                ],
            ],
            'transports' => [
                $transport => $this->transport($transport),
            ],
            // Optional mail logging records outbound mail metadata without replacing the transport.
            'logging' => [
                'enabled' => filter_var(Environment::get('MAIL_LOG_ENABLED', 'true'), FILTER_VALIDATE_BOOL),
                'channel' => Environment::get('MAIL_LOG_CHANNEL', 'app'),
                'level' => Environment::get('MAIL_LOG_LEVEL', 'info'),
            ],
        ];
    }

    /**
     * @return array<array-key, mixed>
     */
    private function transport(string $transport): array
    {
        return match ($transport) {
            // SMTP works with Mailpit locally or a real SMTP provider in deployed environments.
            'smtp' => [
                'driver' => 'smtp',
                'host' => Environment::get('MAIL_SMTP_HOST', '127.0.0.1'),
                'port' => (int) Environment::get('MAIL_SMTP_PORT', '587'),
                'username' => Environment::get('MAIL_SMTP_USERNAME', ''),
                'password' => Environment::get('MAIL_SMTP_PASSWORD', ''),
                'encryption' => Environment::get('MAIL_SMTP_ENCRYPTION', 'tls'),
                'timeout_seconds' => (int) Environment::get('MAIL_SMTP_TIMEOUT_SECONDS', '30'),
            ],
            // Sendmail executes the configured local sendmail-compatible command.
            'sendmail' => [
                'driver' => 'sendmail',
                'command' => Environment::get('MAIL_SENDMAIL_COMMAND', '/usr/sbin/sendmail -t -i'),
            ],
            // SES requires AWS-compatible region and credentials.
            'ses' => [
                'driver' => 'ses',
                'region' => Environment::get('MAIL_SES_REGION', 'us-east-1'),
                'access_key' => Environment::get('MAIL_SES_ACCESS_KEY', ''),
                'secret_key' => Environment::get('MAIL_SES_SECRET_KEY', ''),
            ],
            // Mailgun requires a domain, API secret, and endpoint.
            'mailgun' => [
                'driver' => 'mailgun',
                'domain' => Environment::get('MAILGUN_DOMAIN', ''),
                'secret' => Environment::get('MAILGUN_SECRET', ''),
                'endpoint' => Environment::get('MAILGUN_ENDPOINT', 'https://api.mailgun.net'),
            ],
            // Log transport records messages without delivering them.
            'log' => [
                'driver' => 'log',
            ],
            default => [
                'driver' => $transport,
            ],
        };
    }
}
