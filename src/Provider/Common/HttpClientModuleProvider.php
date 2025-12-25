<?php
declare(strict_types=1);

namespace LPwork\Provider\Common;

use DI\ContainerBuilder;
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
            SymfonyHttpClientInterface::class => \DI\factory(
                static function (): SymfonyHttpClientInterface {
                    return HttpClient::create();
                },
            ),
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
