<?php
declare(strict_types=1);

namespace LPwork\Provider\Common;

use DI\ContainerBuilder;
use LPwork\Config\Contract\ConfigRepositoryInterface;
use LPwork\Http\Response\JsonResponseFactory;
use LPwork\Http\Response\ResponseFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;
use Symfony\Contracts\HttpClient\HttpClientInterface as SymfonyHttpClientInterface;

/**
 * Registers HTTP client factories and PSR-17/18 bindings.
 */
final class HttpClientModuleProvider
{
    /**
     * @param ContainerBuilder $containerBuilder
     */
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            Psr17Factory::class => \DI\factory(static fn(): Psr17Factory => new Psr17Factory()),
            RequestFactoryInterface::class => \DI\get(Psr17Factory::class),
            ResponseFactoryInterface::class => \DI\get(Psr17Factory::class),
            StreamFactoryInterface::class => \DI\get(Psr17Factory::class),
            UriFactoryInterface::class => \DI\get(Psr17Factory::class),
            UploadedFileFactoryInterface::class => \DI\get(Psr17Factory::class),
            ServerRequestFactoryInterface::class => \DI\get(Psr17Factory::class),
            JsonResponseFactory::class => \DI\autowire(JsonResponseFactory::class),
            ResponseFactory::class => \DI\autowire(ResponseFactory::class),
            SymfonyHttpClientInterface::class => \DI\factory(static function (
                ConfigRepositoryInterface $config,
            ): SymfonyHttpClientInterface {
                /** @var array<string, mixed> $httpClient */
                $httpClient = (array) $config->get('app.http_client', []);
                $baseUri = \trim((string) ($httpClient['base_uri'] ?? ''));
                $timeout = (float) ($httpClient['timeout'] ?? 30.0);
                $maxRedirects = (int) ($httpClient['max_redirects'] ?? 10);
                $verify = (bool) ($httpClient['verify'] ?? true);
                $headers = (array) ($httpClient['headers'] ?? []);

                $options = [
                    'timeout' => $timeout,
                    'max_redirects' => $maxRedirects,
                    'verify_peer' => $verify,
                    'verify_host' => $verify,
                ];

                if ($baseUri !== '') {
                    $options['base_uri'] = $baseUri;
                }

                if ($headers !== []) {
                    $options['headers'] = $headers;
                }

                return HttpClient::create($options);
            }),
            ClientInterface::class => \DI\factory(static function (
                SymfonyHttpClientInterface $httpClient,
                StreamFactoryInterface $streamFactory,
                ResponseFactoryInterface $responseFactory,
            ): ClientInterface {
                return new Psr18Client($httpClient, $responseFactory, $streamFactory);
            }),
        ]);
    }
}
