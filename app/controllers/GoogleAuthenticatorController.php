<?php
namespace App\Controllers;
use App\Models\User;
use Slim\Http\Request;
use Slim\Http\Response;
use App\Helpers\Google2FA;
use Endroid\QrCode\QrCode;
use Delight\Cookie\Session;
use App\Models\Authentication;
use App\Models\AccountCredential;
use Illuminate\Database\QueryException;
use Endroid\QrCode\ErrorCorrectionLevel;
class GoogleAuthenticatorController extends BaseController {
	protected $container;
	
	const APP_NAME = 'PasswordTools';
	const VERIFICATION_WINDOW = 1;
	const VERIFICATION_SESSION_NAME = 'user_token_verified';
	const DISABLE_VERIFICATION_RESPONSE_IF_VERIFIED_ALREADY = true;

	public function __construct($container)
	{
		$this->container = $container;
	}

	// get 2FA page
	public function getTwoFactorPage(Request $request, Response $response, array $args)
	{
		return $this->container->view->render($response, '/templates/tfa.twig', [
			'user' => Session::get('user'),
			'title' => 'Two-Factor Authentication',
			'styles' => [
				'tfa'
			],
			'scripts' => [
			]
		]);
	}

	// post to backend endpoint
	public function postTryAuthentication(Request $request, Response $response, array $args)
	{
		$requestToken = sanitize($request->getParam('token'));

		// check for a validated user
		if (!$user = Session::get('user'))
		{
			$data = [
				'response' => [
					'success' => false,
					'message' => 'No authenticated user has been located.'
				]
			];
			return $response->withHeader('Content-Type', 'application/json')->withJson($data, 401, JSON_UNESCAPED_UNICODE);
		}

		// check if the token was provided in the request
		if (isNullOrEmptyString($requestToken))
		{
			$data = [
				'response' => [
					'success' => false,
					'message' => 'Token was not provided in the request body.'
				]
			];
			return $response->withHeader('Content-Type', 'application/json')->withJson($data, 401, JSON_UNESCAPED_UNICODE);
		}

		// get user authentication model
		$authentication = $user->authentication;
		if (is_null($authentication) && is_null($authentication->auth_key))
		{
			$data = [
				'response' => [
					'success' => false,
					'message' => 'Your authentication model is corrupted. Please contact your administrator.'
				]
			];
			return $response->withHeader('Content-Type', 'application/json')->withJson($data, 401, JSON_UNESCAPED_UNICODE);
		}

		$verified = Session::has(self::VERIFICATION_SESSION_NAME) ? Session::get(self::VERIFICATION_SESSION_NAME) : false;
		$tokenValid = Google2FA::verify_key($authentication->auth_key, $requestToken, self::VERIFICATION_WINDOW);
		if ($verified)
		{
			if (self::DISABLE_VERIFICATION_RESPONSE_IF_VERIFIED_ALREADY)
			{
				return $response->withHeader('Content-Type', 'application/json')->withJson([], 200, JSON_UNESCAPED_UNICODE);
			}
		}

		if ($tokenValid)
		{
			Session::set(self::VERIFICATION_SESSION_NAME, true);
			$data = [
				'user_id' => $user->id,
				'token_valid' => $tokenValid
			];
			return $response->withRedirect($this->container->router->pathFor('get-password-dashboard', $data));
		}
	}

	public function getNewAuthenticator(Request $request, Response $response, array $args)
	{
		// check for a validated user
		if (!$user = Session::get('user'))
		{
			$data = [
				'response' => [
					'success' => false,
					'message' => 'No authenticated user has been located.'
				]
			];
			return $response->withHeader('Content-Type', 'application/json')->withJson($data, 401, JSON_UNESCAPED_UNICODE);
		}

		// if user has authentication model
		$authentication = $user->authentication;
		if ($authentication != null && $authentication->auth_key != null)
		{

			// path for QR code
			$path = $this->getAuthenticationPath($user, $authentication->auth_key);

			// create QR code and encode
			$qr = $this->getQR($path);

			// write data to response
			return $this->getResponse($response, $qr);
		}

		// generate and update key since it doesn't exist
		$key = Google2FA::generate_secret_key();
		try {
			$created = Authentication::create([
				'user_id' => $user->id,
				'auth_key' => $key
			]);
		} catch (QueryException $e) {
			die($e->getMessage());
		}

		// path for QR code
		$path = $this->getAuthenticationPath($user, $key);

		// create QR code and encode
		$qr = $this->getQR($path);

		// write data to response
		return $this->getResponse($response, $qr);
	}

	private function getAuthenticationPath(User $user, string $key)
	{
		return "otpauth://totp/$user->username?secret=$key&issuer=" . self::APP_NAME;
	}

	private function getQR(string $string)
	{

		$qr = new QrCode($string);
		$qr->setSize(200);
		$qr->setMargin(3);
		$qr->setEncoding('UTF-8');
		$qr->setWriterByName('png');
		$qr->setErrorCorrectionLevel(ErrorCorrectionLevel::HIGH);
		return $qr;
	}

	private function getResponse(Response $response, QrCode $qr)
	{
		$response->getBody()->write($qr->writeString());
		return $response->withHeader('Content-Type', $qr->getContentType());
	}
}