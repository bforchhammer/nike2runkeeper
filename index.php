<?php
require 'vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

$app = new Silex\Application();
$app['debug'] = TRUE;

// Load yaml config as a shared service.
$app['config'] = $app->share(function() {
    return Yaml::parse(__DIR__ . '/config.yml');
});

// Register the Nike+ API as a shared service.
$app['nike'] = $app->share(function ($app) {
    if (empty($app['config']['nike']['username'])) {
        throw new Exception("Property nike.username not configured in config.yml!");
    }
    if (empty($app['config']['nike']['password'])) {
        throw new Exception("Property nike.password not configured in config.yml!");
    }
    return new NikePlusPHP2\Api($app['config']['nike']['username'], $app['config']['nike']['password']);
});

// Register home route.
$app->get('/', function () use ($app) {
    $data = $app['nike']->activitiesOffsetLimit(1,1)->data;
    $activity = $data[0];
    $text = "\n" . 'Date: ' . $activity->startTime;
    $text .= "\n" . 'Distance: ' . number_format($activity->metricSummary->distance, 1) . 'km';
    $text .= "\n" . 'Duration: ' . $activity->metricSummary->duration;

    $gps = $app['nike']->activityGPS($activity->activityId);
    $text .= print_r($gps,1);

    return '<pre>' . $text . '</pre>';
});

$app->run();
