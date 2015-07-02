<?php

/*
 * This file is part of the qandidate/toggle-api package.
 *
 * (c) Qandidate.com <opensource@qandidate.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Igorw\Silex\ConfigServiceProvider;
use Predis\Client;
use Qandidate\Toggle\Serializer\OperatorConditionSerializer;
use Qandidate\Toggle\Serializer\OperatorSerializer;
use Qandidate\Toggle\Serializer\ToggleSerializer;
use Qandidate\Toggle\ToggleCollection\PredisCollection;
use Qandidate\Toggle\ToggleManager;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

$app = new Application();

$env = new \Dotenv\Dotenv(__DIR__ . '/../');
$env->load();
$env->required(array(
    'TOGGLE__DEBUG',
    'TOGGLE__REDIS_DSN',
    'TOGGLE__ALLOWED_ORIGINS',
    'TOGGLE__PREFIX',
));

$app['debug']                 = getenv('TOGGLE__DEBUG');
$app['redis_dsn']             = getenv('TOGGLE__REDIS_DSN');
$app['toggle.manager.prefix'] = getenv('TOGGLE__PREFIX');
$app['allowed_origins']       = json_decode(getenv('TOGGLE__ALLOWED_ORIGINS'));

if (JSON_ERROR_NONE !== json_last_error()) {
    throw new RuntimeException('Failed to json_decode TOGGLE__ALLOWED_ORIGINS');
}

$app['env'] = getenv('env') ?: 'dev';

if ($app['env'] === 'test') {
    $app['toggle.manager.prefix'] .= '_test';
}

$app->register(new Predis\Silex\PredisServiceProvider(), array(
    'predis.parameters' => $app['redis_dsn'],
));

$app['toggle.manager.collection'] = $app->share(function ($app) {
    return new PredisCollection($app['toggle.manager.prefix'], $app['predis']);
});

$app['toggle.manager']        = $app->share(function ($app) {
    return new ToggleManager($app['toggle.manager.collection']);
});

$app['toggle.operator_condition_serializer'] = $app->share(function($app) {
    return new OperatorConditionSerializer(new OperatorSerializer());
});

$app['toggle.serializer'] = $app->share(function($app) {
    return new ToggleSerializer($app['toggle.operator_condition_serializer']);
});

$app->get('/toggles', function() use ($app) {
    $toggles = $app['toggle.manager']->all();

    $serializedToggles = array();

    foreach ($toggles as $toggle) {
        $serializedToggles[] = $app['toggle.serializer']->serialize($toggle);
    }

    return $app->json($serializedToggles);
});

$app['request_to_toggle'] = $app->protect(function(Request $request) use ($app) {
    $serialized = $request->getContent();

    $data = json_decode($serialized, true);

    if (json_last_error() != JSON_ERROR_NONE) {
        return null;
    }

    return $app['toggle.serializer']->deserialize($data);
});

$app->put('/toggles/{name}', function(Request $request, $name) use ($app) {
    if (null === $toggle = $app['request_to_toggle']($request)) {
        return new Response('Malformed json in post body.', 400);
    }

    if ($name !== $toggle->getName()) {
        return new Response('Name of toggle can not be changed.', 400);
    }

    $app['toggle.manager']->update($toggle);

    return new Response('', 204);
});

$app->delete('/toggles/{name}', function($name) use ($app) {
    $removed = $app['toggle.manager']->remove($name);

    if ( ! $removed) {
        return new Response(sprintf('Unable to delete toggle "%s"', $name), 400);
    }

    return new Response('OK');
});

return $app;
