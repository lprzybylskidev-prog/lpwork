<?php
declare(strict_types=1);

namespace LPwork\Provider\Common;

use DI\ContainerBuilder;
use Faker\Factory as FakerFactory;
use Faker\Generator as FakerGenerator;
use LPwork\Environment\Env;
use LPwork\Translation\Contract\TranslationProviderInterface;
use LPwork\Translation\Contract\TranslatorFactoryInterface;
use LPwork\Translation\TranslationProvider;
use LPwork\Translation\TranslatorFactory;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Registers translation services and Faker locale binding.
 */
final class TranslationModuleProvider
{
    /**
     * @param ContainerBuilder $containerBuilder
     */
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            TranslatorFactory::class => \DI\autowire(TranslatorFactory::class),
            TranslatorFactoryInterface::class => \DI\get(TranslatorFactory::class),
            TranslationProvider::class => \DI\autowire(TranslationProvider::class),
            TranslationProviderInterface::class => \DI\get(TranslationProvider::class),
            TranslatorInterface::class => \DI\factory(static function (
                TranslationProviderInterface $provider,
            ): TranslatorInterface {
                return $provider->createTranslator();
            }),
            FakerGenerator::class => \DI\factory(static function (Env $env): FakerGenerator {
                $locale = $env->getString('APP_LOCALE', 'en');

                return FakerFactory::create($locale);
            }),
        ]);
    }
}
