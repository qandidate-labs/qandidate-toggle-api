<?php

use Predis\Client;
use Qandidate\Toggle\ToggleCollection\PredisCollection;
use Qandidate\Toggle\ToggleManager;
use Silex\Application;

$app = new Application();

$app->register(new Predis\Silex\PredisServiceProvider(), array(
    'predis.parameters' => 'tcp://127.0.0.1:6379'
));

$app['toggle_manager.prefix']     = 'toggle_namespace';
$app['toggle_manager.collection'] = $app->share(function ($app) {
    return new PredisCollection($app['toggle_manager.prefix'], $app['predis']);
});

$app['toggle_manager']        = $app->share(function ($app) {
    return new ToggleManager($app['toggle_manager.collection']);
});

$app->get('/toggles', function() use ($app) {
    $toggles = $app['toggle_manager']->all();

    $names = array_keys($toggles);

    return $app->json(array('toggles' => $names));
});

return $app;
