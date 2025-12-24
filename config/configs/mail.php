<?php
declare(strict_types=1);

use LPwork\Environment\Env;

/** @var Env $env */

/**
 * Mail transports configuration (Symfony Mailer).
 * default_connection: name of the transport used when none specified.
 * connections: map of named transports with DSN and options.
 */
return [
    // Name of the default mail connection.
    'default_connection' => $env->getString('MAIL_DEFAULT_CONNECTION', 'smtp'),
    'connections' => [
        'smtp' => [
            // Symfony Mailer DSN (supports SMTP, Mailtrap, etc.).
            'dsn' => $env->getString('MAIL_DSN', 'smtp://localhost'),
            // Timeout in seconds (null uses library default).
            'timeout' => $env->getInt('MAIL_TIMEOUT', 0) ?: null,
        ],
    ],
];
