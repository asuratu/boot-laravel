<?php

namespace ZhuiTech\BootLaravel\Guards;

use Illuminate\Container\Container;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Laravel\Passport\Passport;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use ZhuiTech\BootLaravel\Models\User;

class JwtTokenGuard
{
	/**
	 * The resource server instance.
	 *
	 * @var \League\OAuth2\Server\ResourceServer
	 */
	protected $server;

	/**
	 * Create a new token guard instance.
	 *
	 * @param \League\OAuth2\Server\ResourceServer $server
	 * @param \Illuminate\Contracts\Encryption\Encrypter $encrypter
	 */
	public function __construct()
	{
		$this->server = new ResourceServer(
			new JwtTokenRepository(),
			$this->makeCryptKey('public')
		);
	}

	/**
	 * Get the user for the incoming request.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return mixed
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function user(Request $request)
	{
		return $this->authenticateViaBearerToken($request);
	}

	/**
	 * Authenticate the incoming request via the Bearer token.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return mixed
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	protected function authenticateViaBearerToken($request)
	{
		if (!$psr = $this->getPsrRequestViaBearerToken($request)) {
			return;
		}

		$user_id = $psr->getAttribute('oauth_user_id') ?: null;
		if ($user_id) {
			return new User(['id' => $user_id]);
		}
	}

	/**
	 * Authenticate and get the incoming PSR-7 request via the Bearer token.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return \Psr\Http\Message\ServerRequestInterface
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	protected function getPsrRequestViaBearerToken($request)
	{
		// First, we will convert the Symfony request to a PSR-7 implementation which will
		// be compatible with the base OAuth2 library. The Symfony bridge can perform a
		// conversion for us to a Zend Diactoros implementation of the PSR-7 request.
		$psr = (new PsrHttpFactory(
			new Psr17Factory,
			new Psr17Factory,
			new Psr17Factory,
			new Psr17Factory
		))->createRequest($request);

		try {
			return $this->server->validateAuthenticatedRequest($psr);
		} catch (OAuthServerException $e) {
			$request->headers->set('Authorization', '', true);

			Container::getInstance()->make(
				ExceptionHandler::class
			)->report($e);
		}
	}

	/**
	 * Create a CryptKey instance without permissions check.
	 *
	 * @param string $key
	 * @return \League\OAuth2\Server\CryptKey
	 */
	protected function makeCryptKey($type)
	{
		$key = str_replace('\\n', "\n", config('passport.' . $type . '_key'));

		if (!$key) {
			$key = 'file://' . Passport::keyPath('oauth-' . $type . '.key');
		}

		return new CryptKey($key, null, false);
	}
}
