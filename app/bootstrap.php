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

use Qandidate\Toggle\Serializer\OperatorConditionSerializer;
use Qandidate\Toggle\Serializer\OperatorSerializer;
use Qandidate\Toggle\Serializer\ToggleSerializer;
use Qandidate\Toggle\ToggleCollection\PredisCollection;
use Qandidate\Toggle\ToggleManager;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$app = new Application();

$env = \Dotenv\Dotenv::createImmutable(__DIR__.'/../');
$env->load();
$env->required([
    'TOGGLE__DEBUG',
    'TOGGLE__REDIS_DSN',
    'TOGGLE__ALLOWED_ORIGINS',
    'TOGGLE__PREFIX',
]);

$app['debug'] = getenv('TOGGLE__DEBUG');
$app['redis_dsn'] = getenv('TOGGLE__REDIS_DSN');
$app['toggle.manager.prefix'] = getenv('TOGGLE__PREFIX');
$app['allowed_origins'] = getenv('TOGGLE__ALLOWED_ORIGINS') ? json_decode((string) getenv('TOGGLE__ALLOWED_ORIGINS'), true) : [];

if (JSON_ERROR_NONE !== json_last_error()) {
    throw new RuntimeException('Failed to json_decode TOGGLE__ALLOWED_ORIGINS');
}

$app['env'] = getenv('env') ?: 'dev';

if ('test' === $app['env']) {
    $app['toggle.manager.prefix'] .= '_test';
}

$app->register(new Predis\Silex\ClientServiceProvider(), [
    'predis.parameters' => $app['redis_dsn'],
]);

$app['toggle.manager.collection'] = $app->factory(function ($app) {
    return new PredisCollection($app['toggle.manager.prefix'], $app['predis']);
});

$app['toggle.manager'] = $app->factory(function ($app) {
    return new ToggleManager($app['toggle.manager.collection']);
});

$app['toggle.operator_condition_serializer'] = $app->factory(function ($app) {
    return new OperatorConditionSerializer(new OperatorSerializer());
});

$app['toggle.serializer'] = $app->factory(function ($app) {
    return new ToggleSerializer($app['toggle.operator_condition_serializer']);
});

$app->get('/toggles', function () use ($app) {
    $toggles = $app['toggle.manager']->all();

    $serializedToggles = [];

    foreach ($toggles as $toggle) {
        $serializedToggles[] = $app['toggle.serializer']->serialize($toggle);
    }

    return $app->json($serializedToggles);
});

$app['request_to_toggle'] = $app->protect(function (Request $request) use ($app) {
    $data = json_decode((string) $request->getContent(), true);

    if (JSON_ERROR_NONE != json_last_error()) {
        return null;
    }

    return $app['toggle.serializer']->deserialize($data);
});

$app->put('/toggles/{name}', function (Request $request, $name) use ($app) {
    if (null === $toggle = $app['request_to_toggle']($request)) {
        return new Response('Malformed json in post body.', 400);
    }

    if ($name !== $toggle->getName()) {
        return new Response('Name of toggle can not be changed.', 400);
    }

    $app['toggle.manager']->update($toggle);

    return new Response('', 204);
});

$app->delete('/toggles/{name}', function ($name) use ($app) {
    $app['toggle.manager']->remove($name);

    return new Response('OK');
});

return $app;
