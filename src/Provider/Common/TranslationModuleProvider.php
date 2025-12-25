<?php
declare(strict_types=1);

namespace LPwork\Provider\Common;

use DI\ContainerBuilder;
use Faker\Factory as FakerFactory;
use Faker\Generator as FakerGenerator;
use LPwork\Environment\Env;
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
            TranslationProvider::class => \DI\autowire(TranslationProvider::class),
            TranslatorInterface::class => \DI\factory(static function (
                TranslationProvider $provider,
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
