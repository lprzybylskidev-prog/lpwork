<?php
declare(strict_types=1);

namespace LPwork\Bootstrap;

use DI\Container;
use DI\ContainerBuilder;
use InvalidArgumentException;
use LPwork\Exception\PhpErrorException;
use Symfony\Component\Dotenv\Dotenv;
use LPwork\Config\Contract\ConfigRepositoryInterface;
use LPwork\Kernel\CliKernel;
use LPwork\Kernel\HttpKernel;
use LPwork\Provider\CliProvider;
use LPwork\Provider\CommonProvider;
use LPwork\Provider\Contract\ProviderInterface;
use LPwork\Provider\ProviderFactory;
use LPwork\Provider\HttpProvider;
use LPwork\Runtime\RuntimeType;
use LPwork\Time\TimezoneContext;
use Config\AppProvider;

/**
 * Handles the initial bootstrapping of the LPwork framework.
 */
class Bootstrap
{
    /**
     * Builds a lightweight container used to instantiate providers.
     *
     * @return Container
     */
    private function buildProviderContainer(): Container
    {
        $builder = new ContainerBuilder();
        $builder->useAutowiring(true);
        $builder->useAttributes(true);

        return $builder->build();
    }

    /**
     * Boots the framework for the detected runtime context.
     *
     * @return void
     */
    public function run(): void
    {
        $this->bootstrapEnvironment();
        $this->registerErrorHandler();
        $runtimeType = $this->detectRuntimeType();
        $container = $this->buildContainer($runtimeType);

        $this->configurePhpRuntime(
            $container->get(ConfigRepositoryInterface::class),
            $container->get(TimezoneContext::class),
        );
        $this->runKernel($runtimeType, $container);
    }

    /**
     * Determines the runtime environment type.
     *
     * @return RuntimeType
     */
    private function detectRuntimeType(): RuntimeType
    {
        if (\in_array(\PHP_SAPI, ['cli', 'phpdbg'], true)) {
            return RuntimeType::Cli;
        }

        return RuntimeType::Http;
    }

    /**
     * Builds a configured container for the given runtime.
     *
     * @param RuntimeType $runtimeType
     *
     * @return Container
     */
    private function buildContainer(RuntimeType $runtimeType): Container
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->useAutowiring(true);
        $containerBuilder->useAttributes(true);

        $providerFactory = new ProviderFactory($this->buildProviderContainer());

        foreach ($this->resolveProviders($runtimeType, $providerFactory) as $provider) {
            $provider->register($containerBuilder);
        }

        return $containerBuilder->build();
    }

    /**
     * Returns providers required for the current runtime.
     *
     * @param RuntimeType $runtimeType
     * @param ProviderFactory $providerFactory
     *
     * @return array<int, ProviderInterface>
     */
    private function resolveProviders(
        RuntimeType $runtimeType,
        ProviderFactory $providerFactory,
    ): array {
        $providers = [
            $providerFactory->create(CommonProvider::class),
            $providerFactory->create(AppProvider::class),
        ];

        if ($runtimeType === RuntimeType::Cli) {
            $providers[] = $providerFactory->create(CliProvider::class);
        } else {
            $providers[] = $providerFactory->create(HttpProvider::class);
        }

        return $providers;
    }

    /**
     * Runs the kernel matching the runtime type.
     *
     * @param RuntimeType $runtimeType
     * @param Container   $container
     *
     * @return void
     */
    private function runKernel(RuntimeType $runtimeType, Container $container): void
    {
        if ($runtimeType === RuntimeType::Cli) {
            $container->get(CliKernel::class)->run();

            return;
        }

        $container->get(HttpKernel::class)->run();
    }

    /**
     * Loads environment variables from the project root.
     *
     * @return void
     */
    private function bootstrapEnvironment(): void
    {
        $dotenv = new Dotenv();
        $root = \dirname(__DIR__, 2);
        $envFile = $root . '/.env';

        if (\is_file($envFile)) {
            $dotenv->loadEnv($envFile);
        }
    }

    /**
     * Registers a global error handler converting PHP errors to exceptions.
     *
     * @return void
     */
    private function registerErrorHandler(): void
    {
        \set_error_handler(static function (
            int $severity,
            string $message,
            string $file = '',
            int $line = 0,
        ): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }

            throw new PhpErrorException($message, $severity, 500, $file, $line);
        });
    }

    /**
     * Applies PHP runtime settings from configuration.
     *
     * @param ConfigRepositoryInterface $config
     * @param TimezoneContext           $timezoneContext
     *
     * @return void
     */
    private function configurePhpRuntime(
        ConfigRepositoryInterface $config,
        TimezoneContext $timezoneContext,
    ): void {
        \date_default_timezone_set($timezoneContext->name());

        $errorReportingMask = $this->resolveErrorReportingMask(
            $config->getString('php.error_reporting', 'E_ALL'),
        );
        \error_reporting($errorReportingMask);

        $errorLog = $config->getString('php.error_log', '');
        if ($errorLog !== '') {
            \ini_set('log_errors', '1');
            \ini_set('error_log', $errorLog);
        }

        \ini_set('memory_limit', $config->getString('php.memory_limit', '-1'));

        $maxExecutionTime = $config->getInt('php.max_execution_time', 0);
        \ini_set('max_execution_time', (string) $maxExecutionTime);
    }

    /**
     * Parses an error reporting expression into an integer bitmask.
     *
     * @param string $expression
     *
     * @return int
     */
    private function resolveErrorReportingMask(string $expression): int
    {
        $normalized = \trim($expression);

        if ($normalized === '') {
            return \E_ALL;
        }

        if (\ctype_digit($normalized)) {
            return (int) $normalized;
        }

        $tokens = \preg_split(
            '/\s*([|&])\s*/',
            $normalized,
            -1,
            \PREG_SPLIT_DELIM_CAPTURE | \PREG_SPLIT_NO_EMPTY,
        );

        if ($tokens === false || $tokens === []) {
            throw new InvalidArgumentException(
                'Error reporting expression could not be tokenized.',
            );
        }

        $result = null;
        $pendingOperator = null;

        foreach ($tokens as $token) {
            if ($token === '|' || $token === '&') {
                if ($pendingOperator !== null) {
                    throw new InvalidArgumentException(
                        'Unexpected operator sequence in error reporting expression.',
                    );
                }

                $pendingOperator = $token;
                continue;
            }

            $operand = $this->resolveErrorReportingOperand($token);

            if ($result === null) {
                $result = $operand;
                continue;
            }

            if ($pendingOperator === null) {
                throw new InvalidArgumentException(
                    'Missing operator in error reporting expression.',
                );
            }

            if ($pendingOperator === '|') {
                $result |= $operand;
            } else {
                $result &= $operand;
            }

            $pendingOperator = null;
        }

        if ($result === null) {
            throw new InvalidArgumentException('Empty error reporting expression.');
        }

        if ($pendingOperator !== null) {
            throw new InvalidArgumentException('Trailing operator in error reporting expression.');
        }

        return $result;
    }

    /**
     * Resolves an operand token into its integer value (supports ~E_* and numbers).
     *
     * @param string $token
     *
     * @return int
     */
    private function resolveErrorReportingOperand(string $token): int
    {
        $token = \trim($token);

        $negated = false;
        while (\str_starts_with($token, '~')) {
            $negated = !$negated;
            $token = \substr($token, 1);
        }

        if ($token === '') {
            throw new InvalidArgumentException('Empty operand in error reporting expression.');
        }

        if (\ctype_digit($token)) {
            $value = (int) $token;
        } else {
            $constantName = \strtoupper($token);
            if (!\defined($constantName)) {
                throw new InvalidArgumentException(
                    \sprintf('Unknown error reporting constant "%s".', $token),
                );
            }

            /** @var int $value */
            $value = (int) \constant($constantName);
        }

        return $negated ? ~$value : $value;
    }
}
