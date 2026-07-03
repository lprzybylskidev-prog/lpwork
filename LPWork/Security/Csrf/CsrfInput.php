<?php

declare(strict_types=1);

namespace LPWork\Security\Csrf;

use LPWork\Session\Session;

/**
 * Represents the csrf input framework component.
 */
final class CsrfInput
{
    /**
     * Returns parsed input data from this boundary.
     */
    public static function input(Session $session): string
    {
        $config = new CsrfConfig(
            enabled: true,
            sessionKey: '_csrf_token',
            inputKey: '_token',
            headerName: 'X-CSRF-TOKEN',
            rotate: false,
            perForm: false,
        );

        return self::fromConfig($session, $config);
    }

    /**
     * Creates a CsrfInput instance from from config input.
     */
    public static function fromConfig(Session $session, CsrfConfig $config): string
    {
        $token = new CsrfTokenManager($config)->token($session);

        return sprintf(
            '<input type="hidden" name="%s" value="%s">',
            htmlspecialchars($config->inputKey(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($token, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        );
    }

    /**
     * Performs the for form operation.
     */
    public static function forForm(Session $session, CsrfConfig $config, string $form): string
    {
        $token = new CsrfTokenManager($config)->formToken($session, $form);

        return sprintf(
            '<input type="hidden" name="%s_form" value="%s"><input type="hidden" name="%s" value="%s">',
            htmlspecialchars($config->inputKey(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($form, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($config->inputKey(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($token, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        );
    }
}
