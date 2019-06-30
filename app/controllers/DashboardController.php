<?php
namespace App\Controllers;
use Carbon\Carbon;
use App\Models\User;
use Slim\Http\Request;
use Slim\Http\Response;
use App\Models\Sessions;
use App\Helpers\Google2FA;
use Endroid\QrCode\QrCode;
use Delight\Cookie\Session;
use App\Models\AccountCredential;
use App\Controllers\BaseController;
use Illuminate\Database\QueryException;
use Endroid\QrCode\ErrorCorrectionLevel;
use Illuminate\Database\Eloquent\Collection;

class DashboardController extends BaseController {
	protected $container;

	const ERR_USER_NOT_FOUND = 'No authenticated user has been located';
	const ERR_ACCT_NOT_FOUND = 'No account found';

	public function __construct($container)
	{
		$this->container = $container;
	}

	/**
	 * Get the password dashboard
	 * @param  Request  $request  [description]
	 * @param  Response $response [description]
	 * @param  [type]   $args     [description]
	 * @return [type]             [description]
	 */
	public function getPasswordDashboard(Request $request, Response $response, array $args)
	{
		// check for a validated user
		if (!$user = Session::get('user'))
		{
			$data = [
				'response' => [
					'success' => false,
					'message' => self::ERR_USER_NOT_FOUND
				]
			];
			return $response->withHeader('Content-Type', 'application/json')->withJson($data, 401, JSON_UNESCAPED_UNICODE);
		}

		// download user credentials
		$payload = [];
		$credentials = AccountCredential::where([
			['user_id', '=', $user->id],
			['deleted_at', '=', null]
		])->get();
		foreach ($credentials as $credential)
		{
			array_push($payload, $credential->toHydrationArray());
		}

		// show password dashboard
		return $this->container->view->render($response, '/templates/password-dashboard.twig', [
			'user' => $user,
			'credentials' => $payload,
			'title' => 'Password Dashboard',
			'styles' => [
				'reset',
				'grid',
				'admin'
			],
			'scripts' => [
				'tippy',
				'passwords'
			]
		]);
	}
}