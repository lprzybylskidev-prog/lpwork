<?php
declare(strict_types=1);

namespace LPwork\Http\Routing\Exception;

/**
 * Thrown when a route exists but the HTTP method is not allowed.
 */
class MethodNotAllowedException extends \RuntimeException
{
    /**
     * @var array<int, string>
     */
    private array $allowed;

    /**
     * @param string              $method
     * @param string              $path
     * @param array<int, string> $allowed
     */
    public function __construct(string $method, string $path, array $allowed)
    {
        parent::__construct(\sprintf('Method %s not allowed for %s', $method, $path), 405);
        $this->allowed = $allowed;
    }

    /**
     * @return array<int, string>
     */
    public function allowedMethods(): array
    {
        return $this->allowed;
    }
}
