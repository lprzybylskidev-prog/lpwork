<?php

declare(strict_types=1);

namespace Tests\support\validation;

use LPWork\Container\Container;
use LPWork\Validation\Providers\ValidationServiceProvider;
use LPWork\Validation\Validator;

final readonly class ValidationTestServices
{
    public static function validator(): Validator
    {
        $container = new Container();
        new ValidationServiceProvider()->register($container);

        $validator = $container->make(Validator::class);

        if (!$validator instanceof Validator) {
            throw new ValidationTestSupportException('Container did not resolve the Validator.');
        }

        return $validator;
    }
}
