<?php
namespace App\Controllers;
use Slim\Http\Request;
use Slim\Http\Response;
abstract class BaseController {
	protected $container;
	protected $logger;

	/**
	 * Build the path for a named route including the base path
	 *
	 * @param string $name        Route name
	 * @param array  $data        Named argument replacement data
	 * @param array  $queryParams Optional query string parameters
	 */
	public function responseWithFlash(Request $request, Response $response, array $data, string $route = '', array $queryParams = [])
	{
		$this->container->flash->addMessage('data', $data);
		return $response->withRedirect($this->container->router->pathFor($route, $queryParams));
	}

	/**
	 * Get base directory from the request object
	 * @param  Request $request current request
	 * @return String|Null base directory
	 */
	public function getBaseDirectory(Request $request = null)
	{
		return !is_null($request) ? (string)$request->getUri()->withPath('')->withQuery('')->withFragment('') : null;
	}

	/**
	 * Log message for this controller
	 * @param  [type] $logger   [description]
	 * @param  string $type     [description]
	 * @param  [type] $function [description]
	 * @param  string $msg   [description]
	 * @return [type]           [description]
	 */
	public function log(string $type, string $function, string $msg)
	{
		if ($this->logger != null) {
			$this->logger->{$type}($function . ": " . $msg);
		}
		else throw new \Exception('Logger instance is null', 1);
	}

	/**
	 * @param Request $request
	 * @param string $cookieName
	 * @return string
	 */
	public function getCookieValue(Request $request, string $cookieName = '')
	{
		$cookies = $request->getCookieParams();
		return isset($cookies[$cookieName]) ? $cookies[$cookieName] : null;
	}
}