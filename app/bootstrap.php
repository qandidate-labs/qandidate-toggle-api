<?php

use Predis\Client;
use Qandidate\Toggle\Serializer\OperatorConditionSerializer;
use Qandidate\Toggle\Serializer\OperatorSerializer;
use Qandidate\Toggle\Serializer\ToggleSerializer;
use Qandidate\Toggle\ToggleCollection\PredisCollection;
use Qandidate\Toggle\ToggleManager;
use Silex\Application;
use Symfony\Component\HttpFoundation\Response;

$app = new Application();


$app['env'] = isset($_ENV['env']) ? $_ENV['env']: 'dev';

if ($app['env'] === 'dev' || $app['env'] === 'test') {
    $app['debug'] = true;
}

$app->register(new Predis\Silex\PredisServiceProvider(), array(
    'predis.parameters' => 'tcp://127.0.0.1:6379'
));

$app['toggle.manager.prefix']     = 'toggle_namespace';
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

    return $app->json(array('toggles' => $serializedToggles));
});

$app->delete('/toggles/{name}', function($name) use ($app) {
    $removed = $app['toggle.manager']->remove($name);

    if ( ! $removed) {
        return new Response(sprintf('Unable to delete toggle "%s"', $name), 400);
    }

    return new Response('OK');
});

if ($app['env'] === 'test') {
    $app['toggle.manager.prefix'] = 'toggle_test_prefix';
}

return $app;
