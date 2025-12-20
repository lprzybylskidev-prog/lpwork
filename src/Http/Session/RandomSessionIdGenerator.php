<?php
declare(strict_types=1);

namespace LPwork\Http\Session;

use LPwork\Http\Session\Contract\SessionIdGeneratorInterface;
use LPwork\Http\Session\Exception\SessionConfigurationException;

/**
 * Generates secure random session identifiers.
 */
class RandomSessionIdGenerator implements SessionIdGeneratorInterface
{
    /**
     * @var int
     */
    private int $length;

    /**
     * @param int $length
     */
    public function __construct(int $length = 40)
    {
        if ($length < 32) {
            throw new SessionConfigurationException(
                "Session ID length must be at least 32 characters.",
            );
        }

        $this->length = $length;
    }

    /**
     * @inheritDoc
     */
    public function generate(): string
    {
        $bytes = \random_bytes((int) \ceil($this->length / 2));

        return \substr(\bin2hex($bytes), 0, $this->length);
    }
}
