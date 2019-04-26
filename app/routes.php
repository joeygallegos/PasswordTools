<?php
use App\Models\User;
use Slim\Http\Request;
use Slim\Http\Response;
use App\Models\PassHash;
use App\Models\Sessions;
use App\Controllers\LoginController;
use App\Controllers\LogoutController;
use App\Controllers\PasswordController;
use App\Middleware\TwoFactorCheckMiddleware;
use App\Controllers\GoogleAuthenticatorController;

$app->get('/', PasswordController::class . ':getPasswordManager')->add(new TwoFactorCheckMiddleware($container, $app->getContainer()->router));
$app->get('/login/', LoginController::class . ':getLoginUser')->setName('login-get');
$app->post('/ajax/login/', LoginController::class . ':postLoginUser')->setName('login-post');

// password manager
$app->post('/passman/create/', PasswordController::class . ':createAccountCredentials')->add(new TwoFactorCheckMiddleware($container, $app->getContainer()->router));
$app->get('/passman/new/', PasswordController::class . ':getPasswordString')->add(new TwoFactorCheckMiddleware($container, $app->getContainer()->router));
$app->post('/passman/change/', PasswordController::class . ':updatePassword')->add(new TwoFactorCheckMiddleware($container, $app->getContainer()->router));
$app->get('/passman/hydrate/', PasswordController::class . ':getCredentials')->add(new TwoFactorCheckMiddleware($container, $app->getContainer()->router));

// generate authenticator image
$app->get('/passman/image/', GoogleAuthenticatorController::class . ':getNewAuthenticator');

// get two factor page
$app->get('/passman/check/', GoogleAuthenticatorController::class . ':getTwoFactorPage')->setName('tfa-challenge-get');

// backend endpoint
$app->post('/passman/authenticate/', GoogleAuthenticatorController::class . ':postTryAuthentication')->setName('tfa-challenge-post');

$app->get('/update/password/{id}/{password}', function($request, $response, $args) use ($app) {
	$id = $args['id'];
	$password = $args['password'];

	$user = User::where(['id' => $id, 'active' => 1])->first();
	if ($user) {
		if (mb_strlen($password) > 0) {
			$user->password = PassHash::hash($password);
			$updated = $user->update();
			if ($updated) {
				echo "This account has been updated";
			}
		}
		else {
			echo "Password not provided";
		}
	}
	else {
		echo "No active user with this User ID found";
	}
});

// logout routes
$app->get('/logout/', LogoutController::class . ':getLogoutPage')->setName('logout-get');
$app->post('/logout/', LogoutController::class . ':postLogout')->setName('logout-post');