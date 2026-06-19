<?php

return [
    'schema' => [
        'register' => base_path('graphql/schema.graphql'),
    ],
    'schema_cache' => [
        'enable' => env('LIGHTHOUSE_CACHE_ENABLE', env('APP_ENV', 'production') !== 'local'),
        'store' => env('LIGHTHOUSE_CACHE_STORE', 'file'),
        'key' => env('LIGHTHOUSE_CACHE_KEY', 'lighthouse-schema'),
    ],
    'query_cache' => [
        'enable' => env('LIGHTHOUSE_QUERY_CACHE_ENABLE', true),
        'store' => env('LIGHTHOUSE_QUERY_CACHE_STORE', 'file'),
        'ttl' => env('LIGHTHOUSE_QUERY_CACHE_TTL', null),
    ],
    'namespaces' => [
        'models' => ['App', 'App\\Models'],
        'queries' => 'App\\GraphQL\\Queries',
        'mutations' => 'App\\GraphQL\\Mutations',
        'subscriptions' => 'App\\GraphQL\\Subscriptions',
        'types' => 'App\\GraphQL\\Types',
        'interfaces' => 'App\\GraphQL\\Interfaces',
        'unions' => 'App\\GraphQL\\Unions',
        'scalars' => 'App\\GraphQL\\Scalars',
        'directives' => ['App\\GraphQL\\Directives'],
        'validators' => ['App\\GraphQL\\Validators'],
    ],
    'security' => [
        'max_query_complexity' => 200,
        'max_query_depth' => 15,
        'disable_introspection' => \GraphQL\Validator\Rules\DisableIntrospection::DISABLED,
    ],
    'pagination' => [
        'default_count' => null,
        'max_count' => null,
    ],
    'debug' => env(
        'LIGHTHOUSE_DEBUG',
        \GraphQL\Error\DebugFlag::INCLUDE_DEBUG_MESSAGE | \GraphQL\Error\DebugFlag::INCLUDE_TRACE
    ),
    'error_handlers' => [
        \Nuwave\Lighthouse\Execution\AuthenticationErrorHandler::class,
        \Nuwave\Lighthouse\Execution\AuthorizationErrorHandler::class,
        \Nuwave\Lighthouse\Execution\ValidationErrorHandler::class,
        \Nuwave\Lighthouse\Execution\ReportingErrorHandler::class,
    ],
    'field_middleware' => [
        \Nuwave\Lighthouse\Schema\Directives\TrimDirective::class,
        \Nuwave\Lighthouse\Schema\Directives\ConvertEmptyStringsToNullDirective::class,
        \Nuwave\Lighthouse\Schema\Directives\SanitizeDirective::class,
        \Nuwave\Lighthouse\Validation\ValidateDirective::class,
        \Nuwave\Lighthouse\Schema\Directives\TransformArgsDirective::class,
        \Nuwave\Lighthouse\Schema\Directives\SpreadDirective::class,
        \Nuwave\Lighthouse\Schema\Directives\RenameArgsDirective::class,
        \Nuwave\Lighthouse\Schema\Directives\DropArgsDirective::class,
    ],
    'global_id_field' => 'id',
    'batched_queries' => true,
    'transactional_mutations' => true,
    'force_fill' => false,
    'batchload_relations' => true,
    'route' => [
        'uri' => '/graphql',
        'name' => 'graphql',
        'middleware' => [],
    ],
    'ide' => [
        'enabled' => env('LIGHTHOUSE_IDE_ENABLED', true),
        'uri' => '/graphiql',
    ],
    'subscription' => null,
    'federation' => [
        'entities_resolver_namespace' => 'App\\GraphQL\\Entities',
    ],
];
