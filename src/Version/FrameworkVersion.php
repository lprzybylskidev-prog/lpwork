<?php
declare(strict_types=1);

namespace LPwork\Version;

/**
 * Represents LPwork framework version.
 */
class FrameworkVersion
{
    /**
     * @var string
     */
    private string $version;

    /**
     * @param string $version
     */
    public function __construct(string $version = '0.0.1')
    {
        $this->version = $version;
    }

    /**
     * @return string
     */
    public function get(): string
    {
        return $this->version;
    }
}
