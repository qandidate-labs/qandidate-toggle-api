<?php

use Predis\Client;
use Qandidate\Toggle\ToggleCollection\PredisCollection;
use Qandidate\Toggle\ToggleManager;
use Silex\Application;

$app = new Application();

$app['env'] = $_ENV['env'] ?: 'dev';

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

$app->get('/toggles', function() use ($app) {
    $toggles = $app['toggle.manager']->all();

    $names = array_keys($toggles);

    return $app->json(array('toggles' => $names));
});

if ($app['env'] === 'test') {
    $app['toggle.manager.prefix'] = 'toggle_test_prefix';
}

return $app;
