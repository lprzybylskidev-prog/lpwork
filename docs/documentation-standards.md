# Documentation Standards

LPWork documentation should help application developers build with the framework without requiring them to learn framework internals first.

## User Documentation

Write primary documentation for people working inside an LPWork application.

- Start with the application-facing concept, then show the files or commands involved.
- Prefer explicit paths such as `App/Modules/Blog/Routes/WebRoutes.php` when a location matters.
- Explain defaults, supported options, required environment values, and external services near the feature that uses them.
- Keep internal architecture explanations out of primary docs unless they are needed to use the feature safely.
- When a feature has side effects, such as mutating secrets, schema, cache state, queues, or persistent storage, make the side effect clear.
- Keep examples small and complete enough to run or adapt.

## Configuration Documentation

Configuration files under `App/Shared/Configs` should document:

- What each option controls.
- Supported drivers or values.
- Required and optional driver settings.
- Defaults and examples.
- External services or PHP extensions required by a driver.
- Production-sensitive behavior, such as destructive commands or secret rotation.

Inline comments should be useful but concise. Longer recipes, driver notes, and cross-feature guidance belong in `docs/`.

## PHPDoc Standards

Public framework classes, contracts, enums, and public methods in `LPWork` should have docblocks that explain API intent. A useful docblock describes why the API exists, what boundary it represents, or what callers can rely on.

Do not add PHPDoc that only repeats native PHP types. Use `@param` descriptions when they add meaning beyond the type, such as accepted formats, allowed values, units, lifecycle expectations, side effects, array shapes, generic collection contents, or whether a path is project-root-relative.

Prefer short intent-focused summaries:

```php
/**
 * Registers application route files declared by an application provider.
 */
```

Use parameter descriptions when the value has a contract that the type cannot express:

```php
/**
 * @param string $path Project-root-relative path to a PHP route declaration file.
 */
```

Avoid empty type restatements:

```php
/**
 * @param string $path
 */
```

Private and protected methods need docblocks only when the behavior, format, lifecycle, side effect, or algorithm is not obvious from the name and surrounding code.
