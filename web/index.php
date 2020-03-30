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
use Symfony\Component\HttpFoundation\Request;

$app = require_once __DIR__.'/../app/bootstrap.php';

$stackedApp = new Cors($app, [
    'allowedOrigins' => $app['allowed_origins'],
    'allowedMethods' => ['DELETE', 'GET', 'PUT', 'POST'],
    'allowedHeaders' => ['accept', 'content-type', 'origin', 'x-requested-with'],
]);

$request = Request::createFromGlobals();

$response = $stackedApp->handle($request);
$response->send();

$app->terminate($request, $response);
