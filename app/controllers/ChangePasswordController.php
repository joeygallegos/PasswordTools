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
use App\Controllers\SendsJsonResponse;
use Endroid\QrCode\ErrorCorrectionLevel;
use Illuminate\Database\Eloquent\Collection;

class ChangePasswordController extends BaseController {
	use SendsJsonResponse;

	protected $container;

	const ERR_USER_NOT_FOUND = 'No authenticated user has been located';
	const ERR_ACCT_NOT_FOUND = 'No account found';

	public function __construct($container)
	{
		$this->container = $container;
	}

	/**
	 * Update the password for an account with the given uuid
	 * @param  Request  $request  [description]
	 * @param  Response $response [description]
	 * @param  [type]   $args     [description]
	 * @return [type]             [description]
	 */
	public function updatePassword(Request $request, Response $response, array $args)
	{
		// session user doesn't exist
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

		// get account uuid
		$uuid = sanitize($request->getParam('uuid'));
		$password = $request->getParam('password');

		// account wasn't found
		if (!$acct = AccountCredential::where('uuid', $uuid)->first())
		{
			$data = [
				'response' => [
					'success' => false,
					'message' => self::ERR_ACCT_NOT_FOUND
				]
			];
			return $response->withHeader('Content-Type', 'application/json')->withJson($data, 401, JSON_UNESCAPED_UNICODE);
		}

		// get enc array
		$encryptionData = openEncrypt($password);

		// update account
		$updated = $acct->update([
			'enc_password' => $encryptionData['string'],
			'enc_method' => $encryptionData['method'],
			'enc_key' => $encryptionData['key'],
			'enc_iv' => $encryptionData['iv'],
			'password_changed_at' => Carbon::now()
		]);

		$data = [
			'response' => [
				'success' => $updated
			]
		];
		return $this->setJsonResponse($response, $data, 200);
	}
}