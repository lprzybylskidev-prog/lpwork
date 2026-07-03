# LPWork Application Architecture

Use this reference when designing or reviewing an application change.

## Explicit Registration

LPWork favors explicit declarations over discovery. Register loadable application pieces through the boundary that owns them:

- Routes through route providers or route declaration classes.
- Service bindings through service providers.
- Config definitions through application config providers.
- Views and assets through view/asset providers.
- Translations through translation providers.
- Commands through console providers.
- Migrations through migration providers.
- Events/listeners through event providers or the relevant registration boundary.

## Provider Discipline

Providers coordinate registration. They should not perform runtime side effects, execute commands, open network connections, mutate storage, read request state, or do heavy work while registering services.

If provider registration grows across independent responsibilities, split it into focused providers, registrars, factories, or definition objects.

## Separation Of Responsibilities

Keep orchestration separate from parsing, validation, resolution, dispatch, state storage, and side effects once the behavior is more than trivial.

Use factories when constructing variants. Use resolvers when turning names, declarations, paths, or metadata into concrete objects. Use managers to coordinate access to configured services without letting them become config parsers, driver factories, validators, and caches all at once.

## Boundaries And Globals

Application internals should not read superglobals directly, emit headers, write raw output, or call session functions. Use the framework request, response, emitter, session, storage, config, and runtime boundaries.

Static/global state should have an explicit lifecycle and be resettable for tests. Avoid adding hidden static state to application helpers.

## Exceptions

Prefer domain-specific exceptions near the application area that owns the failure. Exception names should describe the failure boundary, not the implementation detail.

Use explicit messages for missing configuration, invalid declarations, unsupported drivers, unsafe operations, or failed side effects. Do not throw base exceptions directly for meaningful application boundaries.

## Extending LPWork

Applications can integrate Composer/npm packages through providers and configuration. Add framework-level abstractions only when the user asks for reusable framework behavior. A project-specific integration belongs in `App`; a reusable LPWork capability belongs in `LPWork` with framework tests and docs.
