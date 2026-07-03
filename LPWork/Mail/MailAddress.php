<?php

declare(strict_types=1);

namespace LPWork\Mail;

use const FILTER_VALIDATE_EMAIL;

use function filter_var;

use LPWork\Mail\Exceptions\InvalidMailAddressException;

use function str_contains;
use function str_replace;
use function trim;

/**
 * Represents the mail address framework component.
 */
final readonly class MailAddress
{
    /**
     * Creates a new MailAddress instance.
     */
    public function __construct(
        private string $address,
        private ?string $name = null,
    ) {
        if (filter_var($address, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidMailAddressException($address);
        }

        if ($name !== null && (str_contains($name, "\n") || str_contains($name, "\r"))) {
            throw new InvalidMailAddressException($address);
        }
    }

    /**
     * Registers or stores address.
     */
    public function address(): string
    {
        return $this->address;
    }

    /**
     * Returns the configured name for this object.
     */
    public function name(): ?string
    {
        return $this->name;
    }

    /**
     * Builds or returns formatted.
     */
    public function formatted(): string
    {
        if ($this->name === null || trim($this->name) === '') {
            return $this->address;
        }

        return '"' . str_replace('"', '\"', $this->name) . '" <' . $this->address . '>';
    }

    /**
     * Performs the mailbox operation.
     */
    public function mailbox(): string
    {
        return '<' . $this->address . '>';
    }
}
