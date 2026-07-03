<?php

declare(strict_types=1);

use LPWork\Kernels\Http\HttpEntrypoint;

require_once dirname(__DIR__) . '/vendor/autoload.php';

new HttpEntrypoint(rtrim(dirname(__DIR__), '/'))->run();
