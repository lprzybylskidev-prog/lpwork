# LPWork Module Structure

Use this reference when adding or changing `App/Modules/<Module>`.

## Ownership

A module owns the behavior it exposes: routes, controllers, request objects, views, assets, translations, commands, migrations, events, listeners, and tests.

Shared application concerns belong under `App/Shared` when more than one module needs them. Framework behavior belongs under `LPWork` only when the user asks for framework development.

## Layout

The generated module layout is the default, not a hard rule. A custom internal layout is acceptable when registration remains explicit and easy to trace.

Before adding a new folder or pattern, inspect the existing module shape and mirror it when the responsibility matches.

## Providers

Module providers should register services and declarations. They should not perform runtime side effects, execute commands, open network connections, mutate storage, or do heavy work during registration.

If registration grows across independent responsibilities, split it into focused providers, registrars, factories, or definition objects.

## Routes And Controllers

Keep route declarations descriptive and explicit. Controllers should coordinate the request-specific use case and return framework responses or response-compatible values expected by the dispatcher.

Request objects should wrap and validate input. They should not dispatch application behavior, resolve unrelated dependencies, or emit output.

## Views And Assets

Register views and frontend assets through module providers. Keep backend-rendered PHP views, module assets, translations, and tests close to the module behavior they support.

When UI behavior changes, verify rendered output and responsive/browser behavior when practical.

## Commands, Events, Migrations

Commands should be thin adapters around input, output, validation, and a focused collaborator that performs the work.

Use events/listeners for optional reactions such as audit/logging hooks, metrics, mail/notification triggers, queue handoff, or cache invalidation. Do not use events to hide core control flow.

Migrations should be declared explicitly by the module/application area that owns the schema.
