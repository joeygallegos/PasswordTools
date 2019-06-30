<?php
namespace App\Controllers;
use Slim\Http\Request;
use Slim\Http\Response;
trait SendsJsonResponse {
	public function setJsonResponse(Response $response, $payload, $status = 200)
	{
		return $response->withHeader('Content-Type', 'application/json')->withJson($payload, $status, JSON_UNESCAPED_UNICODE);
	}
}