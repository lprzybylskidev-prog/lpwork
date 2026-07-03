<?php

declare(strict_types=1);

use LPWork\Container\Exceptions\CannotResolveDependencyException;
use LPWork\Container\Exceptions\CircularDependencyException;
use LPWork\Container\Exceptions\InvalidBindingException;

it('describes container entries that cannot be resolved', function (): void {
    expect(CannotResolveDependencyException::classDoesNotExist('App\\Missing')->getMessage())
        ->toBe('Cannot resolve container entry [App\\Missing]: class does not exist.')
        ->and(CannotResolveDependencyException::classIsNotInstantiable('App\\Contract')->getMessage())
        ->toBe('Cannot resolve container entry [App\\Contract]: class is not instantiable.');
});

it('describes constructor parameters that cannot be resolved', function (): void {
    expect(CannotResolveDependencyException::parameterHasNoType('App\\Service', 'mailer')->getMessage())
        ->toBe('Cannot resolve [App\\Service]: constructor parameter [$mailer] has no type.')
        ->and(CannotResolveDependencyException::parameterHasBuiltinType('App\\Service', 'dsn', 'string')->getMessage())
        ->toBe('Cannot resolve [App\\Service]: constructor parameter [$dsn] uses builtin type [string]. Register it with a factory binding.')
        ->and(CannotResolveDependencyException::parameterHasUnsupportedType('App\\Service', 'mailer')->getMessage())
        ->toBe('Cannot resolve [App\\Service]: constructor parameter [$mailer] uses unsupported union or intersection type. Register it with a factory binding.')
        ->and(CannotResolveDependencyException::parameterBindingMissing('App\\Service', 'mailer', 'App\\Mailer')->getMessage())
        ->toBe('Cannot resolve [App\\Service]: constructor parameter [$mailer] expects [App\\Mailer], but no binding exists.')
        ->and(CannotResolveDependencyException::factoryDidNotReturnObject('App\\Service')->getMessage())
        ->toBe('Cannot resolve container entry [App\\Service]: factory must return an object.');
});

it('describes invalid container bindings', function (): void {
    expect(InvalidBindingException::concreteClassDoesNotExist('App\\Mailer', 'App\\MissingMailer')->getMessage())
        ->toBe('Cannot bind [App\\Mailer] to [App\\MissingMailer]: concrete class does not exist.')
        ->and(InvalidBindingException::concreteIsNotInstantiable('App\\Mailer', 'App\\MailerContract')->getMessage())
        ->toBe('Cannot bind [App\\Mailer] to [App\\MailerContract]: concrete class is not instantiable.');
});

it('describes circular dependencies', function (): void {
    expect(CircularDependencyException::fromChain(['App\\A', 'App\\B', 'App\\A'])->getMessage())
        ->toBe('Circular dependency detected while resolving [App\\A]: App\\A -> App\\B -> App\\A.');
});
