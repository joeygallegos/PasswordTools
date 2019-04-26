<?php
use App\Middleware\PreserveInputMiddleware;
use App\Middleware\ValidationErrorsMiddleware;
use App\Models\CacheEngine;
use App\Models\Util;
use App\Validation\Validator;
use Carbon\Carbon;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use RandomLib\Factory as RandomFactory;
use SecurityLib\Strength as Strength;
use Slim\App;
use Slim\Http\Environment;
use Slim\Http\Uri;
use Slim\Views\Twig;
use Slim\Views\TwigExtension;

// default settings
$slimSettings = [];
$slimSettings['addContentLengthHeader'] = false;
$slimSettings['displayErrorDetails'] = false;
$slimSettings['debug'] = false;

$app = new App([
	'settings' => $slimSettings,
	'config' => $config
]);
$container = $app->getContainer();

// random generator
$container['randomGenerator'] = function($container) {
	$randomFactory = new RandomFactory;
	return $randomFactory->getGenerator(new Strength(Strength::HIGH));
};

// cache logger
$container['cacheLogger'] = function($container) {
	$logger = new Logger('App');
	$carbon = new Carbon;
	$formatter = new LineFormatter(null, null, false, true);
	
	$handler = new StreamHandler(getBaseDirectory() . '/logs/' . $carbon->today()->format('m-d-y') . "-cache.log");
	$handler->setFormatter($formatter);
	
	$logger->pushHandler($handler);
	return $logger;
};

// cache logger
$container['debugLogger'] = function($container) {
	$logger = new Logger('App');
	$carbon = new Carbon;
	$formatter = new LineFormatter(null, null, false, true);
	
	$handler = new StreamHandler(getBaseDirectory() . '/logs/' . $carbon->today()->format('m-d-y') . "-debug.log");
	$handler->setFormatter($formatter);
	
	$logger->pushHandler($handler);
	return $logger;
};

// ----------------------------------------------
// TWIG TEMPLATE ENGINE
// ----------------------------------------------
$container['view'] = function($container) {
	$view = null;
	if (in_array(getenv('ENVIRONMENT'), ['assembly', 'staging']))
	{
		$view = new \Slim\Views\Twig("../app/views/", []);
	}
	else {
		// create cache
		$view = new \Slim\Views\Twig("../app/views/", [
			'cache' => '../app/views/cache/'
		]);
	}

	// init and add slim specific extension
	$router = $container->get('router');
	$uri = Uri::createFromEnvironment(new Environment($_SERVER));
	$view->addExtension(new TwigExtension($router, $uri));
	return $view;
};

// ----------------------------------------------
// IMPLEMENT VALIDATOR
// ----------------------------------------------
$container['validator'] = function($container) {
	return new Validator($container);
};
$app->add(new ValidationErrorsMiddleware($container));
$app->add(new PreserveInputMiddleware($container));

// environment setup
if (in_array(getenv('ENVIRONMENT'), ['assembly', 'staging']))
{
	error_reporting(E_ALL);
	ini_set('display_errors', 'On');
	ini_set('display_startup_errors', 'On');
	ini_set('max_execution_time', 0);
	
	$slimSettings = $container->get('settings');
	$slimSettings['displayErrorDetails'] = true;
	$slimSettings['debug'] = true;

	$cacheEngine = new CacheEngine(
		$container->cacheLogger
	);

	$cacheEngine->setSassInDirectory(getBaseDirectory() . '/public/assets/scss');
	$cacheEngine->setSassOutDirectory(getBaseDirectory() . '/public/assets/css');

	$cacheEngine->setJavascriptInFile(getBaseDirectory() . '/public/assets/scripts/main.js');
	$cacheEngine->setJavascriptOutFile(getBaseDirectory() . '/public/assets/scripts/main.min.js');

	$cacheEngine->setOneTimeBuildFiles([
		'grid.scss',
		'flexboxgrid.scss',
		'reset.scss'
	]);

	$cacheEngine->build('Crunched');
}
else {
	error_reporting(0);
	ini_set('display_errors', 'Off');
	ini_set('display_startup_errors', 'Off');
}