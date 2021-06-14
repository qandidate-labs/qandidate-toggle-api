<?php

declare(strict_types=1);

/*
 * This file is part of the qandidate/toggle-api package.
 *
 * (c) Qandidate.com <opensource@qandidate.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__.'/../vendor/autoload.php';

use Asm89\Stack\Cors;
use Qandidate\Application\Toggle\Kernel;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);

$stackedApp = new Cors($kernel, [
    'allowedOrigins' => $_SERVER['TOGGLE__ALLOWED_ORIGINS'] ? json_decode((string) $_SERVER['TOGGLE__ALLOWED_ORIGINS'], true) : [],
    'allowedMethods' => ['DELETE', 'GET', 'PUT', 'POST'],
    'allowedHeaders' => ['accept', 'content-type', 'origin', 'x-requested-with'],
]);

$request = Request::createFromGlobals();
$response = $stackedApp->handle($request);

$response->send();
$kernel->terminate($request, $response);
