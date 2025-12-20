<?php
declare(strict_types=1);

namespace LPwork\Http\Session;

/**
 * Value object describing session cookie settings.
 */
final class SessionCookieParameters
{
    /**
     * @var string
     */
    private string $name;

    /**
     * @var int
     */
    private int $lifetime;

    /**
     * @var string
     */
    private string $path;

    /**
     * @var string
     */
    private string $domain;

    /**
     * @var bool
     */
    private bool $secure;

    /**
     * @var bool
     */
    private bool $httpOnly;

    /**
     * @var string
     */
    private string $sameSite;

    /**
     * @param string $name
     * @param int    $lifetime
     * @param string $path
     * @param string $domain
     * @param bool   $secure
     * @param bool   $httpOnly
     * @param string $sameSite
     */
    public function __construct(
        string $name,
        int $lifetime,
        string $path,
        string $domain,
        bool $secure,
        bool $httpOnly,
        string $sameSite,
    ) {
        $this->name = $name;
        $this->lifetime = $lifetime;
        $this->path = $path;
        $this->domain = $domain;
        $this->secure = $secure;
        $this->httpOnly = $httpOnly;
        $this->sameSite = $sameSite;
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function lifetime(): int
    {
        return $this->lifetime;
    }

    /**
     * @return string
     */
    public function path(): string
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function domain(): string
    {
        return $this->domain;
    }

    /**
     * @return bool
     */
    public function secure(): bool
    {
        return $this->secure;
    }

    /**
     * @return bool
     */
    public function httpOnly(): bool
    {
        return $this->httpOnly;
    }

    /**
     * @return string
     */
    public function sameSite(): string
    {
        return $this->sameSite;
    }

    /**
     * Returns new instance enforcing secure flag.
     *
     * @param bool $secure
     *
     * @return self
     */
    public function withSecure(bool $secure): self
    {
        return new self(
            $this->name,
            $this->lifetime,
            $this->path,
            $this->domain,
            $secure,
            $this->httpOnly,
            $this->sameSite,
        );
    }
}
