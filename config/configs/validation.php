<?php
declare(strict_types=1);

use LPwork\Environment\Env;

/** @var Env $env */

/**
 * Validation configuration (Symfony Validator).
 * enabled: turn validation services on/off.
 * fail_fast: stop on first violation.
 * translation_domain: domain used for validation messages.
 * cache_enabled/cache_pool: optional PSR-6 cache for metadata.
 * constraint_mapping_paths: extra paths with constraint mappings (YAML/XML/PHP).
 */
return [
    // Enable or disable validation services globally (true/false).
    'enabled' => true,
    // Stop validation on first violation (true/false). Defaults to false.
    'fail_fast' => false,
    // Translation domain for validation messages (string).
    'translation_domain' => 'validators',
    // Enable metadata cache (true/false).
    'cache_enabled' => false,
    // Cache pool name used when cache_enabled is true (existing cache pool id).
    'cache_pool' => 'filesystem',
    // Extra constraint mapping paths (array of strings).
    'constraint_mapping_paths' => [],
];
