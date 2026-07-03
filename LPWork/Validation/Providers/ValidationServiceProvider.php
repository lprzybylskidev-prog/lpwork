<?php

declare(strict_types=1);

namespace LPWork\Validation\Providers;

use LPWork\Container\Container;
use LPWork\Container\Exceptions\CannotResolveDependencyException;
use LPWork\ErrorHandling\HttpDebugContext;
use LPWork\Foundation\ServiceProvider;
use LPWork\Validation\Context\ValidationDebugContextProvider;
use LPWork\Validation\Contracts\ValidationRule;
use LPWork\Validation\FormRequestFactory;
use LPWork\Validation\Rules\AfterOrEqualRule;
use LPWork\Validation\Rules\AfterRule;
use LPWork\Validation\Rules\AlphaDashRule;
use LPWork\Validation\Rules\AlphaNumRule;
use LPWork\Validation\Rules\AlphaRule;
use LPWork\Validation\Rules\ArrayRule;
use LPWork\Validation\Rules\AssocRule;
use LPWork\Validation\Rules\BeforeOrEqualRule;
use LPWork\Validation\Rules\BeforeRule;
use LPWork\Validation\Rules\BetweenRule;
use LPWork\Validation\Rules\BooleanRule;
use LPWork\Validation\Rules\ConfirmedRule;
use LPWork\Validation\Rules\ContainsRule;
use LPWork\Validation\Rules\CountRule;
use LPWork\Validation\Rules\DateFormatRule;
use LPWork\Validation\Rules\DateRule;
use LPWork\Validation\Rules\DecimalRule;
use LPWork\Validation\Rules\DifferentRule;
use LPWork\Validation\Rules\DigitsBetweenRule;
use LPWork\Validation\Rules\DigitsRule;
use LPWork\Validation\Rules\DimensionsRule;
use LPWork\Validation\Rules\DistinctRule;
use LPWork\Validation\Rules\EmailRule;
use LPWork\Validation\Rules\EndsWithRule;
use LPWork\Validation\Rules\ExtensionsRule;
use LPWork\Validation\Rules\FileRule;
use LPWork\Validation\Rules\FileSizeRule;
use LPWork\Validation\Rules\GteRule;
use LPWork\Validation\Rules\GtRule;
use LPWork\Validation\Rules\ImageRule;
use LPWork\Validation\Rules\InRule;
use LPWork\Validation\Rules\IntegerRule;
use LPWork\Validation\Rules\IpRule;
use LPWork\Validation\Rules\Ipv4Rule;
use LPWork\Validation\Rules\Ipv6Rule;
use LPWork\Validation\Rules\JsonRule;
use LPWork\Validation\Rules\ListRule;
use LPWork\Validation\Rules\LowercaseRule;
use LPWork\Validation\Rules\LteRule;
use LPWork\Validation\Rules\LtRule;
use LPWork\Validation\Rules\MaxFileSizeRule;
use LPWork\Validation\Rules\MaxItemsRule;
use LPWork\Validation\Rules\MaxRule;
use LPWork\Validation\Rules\MimesRule;
use LPWork\Validation\Rules\MinFileSizeRule;
use LPWork\Validation\Rules\MinItemsRule;
use LPWork\Validation\Rules\MinRule;
use LPWork\Validation\Rules\NotInRule;
use LPWork\Validation\Rules\NullableRule;
use LPWork\Validation\Rules\NumericRule;
use LPWork\Validation\Rules\RegexRule;
use LPWork\Validation\Rules\RequiredArrayKeysRule;
use LPWork\Validation\Rules\RequiredRule;
use LPWork\Validation\Rules\SameRule;
use LPWork\Validation\Rules\SizeRule;
use LPWork\Validation\Rules\SometimesRule;
use LPWork\Validation\Rules\StartsWithRule;
use LPWork\Validation\Rules\StringRule;
use LPWork\Validation\Rules\UppercaseRule;
use LPWork\Validation\Rules\UrlRule;
use LPWork\Validation\Rules\UuidRule;
use LPWork\Validation\ValidationRuleRegistry;
use LPWork\Validation\Validator;

/**
 * Registers validation service provider services with the framework container.
 */
final class ValidationServiceProvider extends ServiceProvider
{
    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Container $container): void
    {
        $container->singleton(ValidationRuleRegistry::class, static function (): ValidationRuleRegistry {
            $registry = new ValidationRuleRegistry();

            foreach (self::builtInRules() as $rule) {
                $registry->register($rule);
            }

            return $registry;
        });

        $container->singleton(Validator::class, static function (Container $container): Validator {
            $rules = $container->make(ValidationRuleRegistry::class);

            if (!$rules instanceof ValidationRuleRegistry) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(ValidationRuleRegistry::class);
            }

            return new Validator($rules);
        });

        $container->singleton(FormRequestFactory::class, static function (Container $container): FormRequestFactory {
            $validator = $container->make(Validator::class);

            if (!$validator instanceof Validator) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(Validator::class);
            }

            return new FormRequestFactory($container, $validator);
        });

        if ($container->has(HttpDebugContext::class)) {
            $context = $container->make(HttpDebugContext::class);

            if (!$context instanceof HttpDebugContext) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(HttpDebugContext::class);
            }

            $context->addProvider(new ValidationDebugContextProvider());
        }
    }

    /**
     * @return list<ValidationRule>
     */
    private static function builtInRules(): array
    {
        return [
            new RequiredRule(),
            new NullableRule(),
            new SometimesRule(),
            new StringRule(),
            new IntegerRule(),
            new NumericRule(),
            new BooleanRule(),
            new ArrayRule(),
            new EmailRule(),
            new MinRule(),
            new MaxRule(),
            new BetweenRule(),
            new SizeRule(),
            new InRule(),
            new NotInRule(),
            new SameRule(),
            new DifferentRule(),
            new ConfirmedRule(),
            new AlphaRule(),
            new AlphaNumRule(),
            new AlphaDashRule(),
            new LowercaseRule(),
            new UppercaseRule(),
            new StartsWithRule(),
            new EndsWithRule(),
            new ContainsRule(),
            new RegexRule(),
            new UrlRule(),
            new UuidRule(),
            new JsonRule(),
            new IpRule(),
            new Ipv4Rule(),
            new Ipv6Rule(),
            new DateRule(),
            new DateFormatRule(),
            new BeforeRule(),
            new BeforeOrEqualRule(),
            new AfterRule(),
            new AfterOrEqualRule(),
            new GtRule(),
            new GteRule(),
            new LtRule(),
            new LteRule(),
            new DigitsRule(),
            new DigitsBetweenRule(),
            new DecimalRule(),
            new ListRule(),
            new AssocRule(),
            new RequiredArrayKeysRule(),
            new DistinctRule(),
            new MinItemsRule(),
            new MaxItemsRule(),
            new CountRule(),
            new FileRule(),
            new ImageRule(),
            new MimesRule(),
            new ExtensionsRule(),
            new MinFileSizeRule(),
            new MaxFileSizeRule(),
            new FileSizeRule(),
            new DimensionsRule(),
        ];
    }
}
