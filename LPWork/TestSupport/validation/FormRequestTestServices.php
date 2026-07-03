<?php

declare(strict_types=1);

namespace Tests\support\validation;

use LPWork\Container\Container;
use LPWork\Validation\FormRequestFactory;
use LPWork\Validation\Providers\ValidationServiceProvider;

final readonly class FormRequestTestServices
{
    public static function factory(?Container $container = null): FormRequestFactory
    {
        $container ??= new Container();
        new ValidationServiceProvider()->register($container);

        $factory = $container->make(FormRequestFactory::class);

        if (!$factory instanceof FormRequestFactory) {
            throw new ValidationTestSupportException('Container did not resolve the FormRequestFactory.');
        }

        return $factory;
    }
}
