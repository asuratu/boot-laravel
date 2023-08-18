<?php

namespace ZhuiTech\BootLaravel\Guards;

use Illuminate\Container\Container;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\TokenRepository;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;

class ProxyTokenGuard extends \Laravel\Passport\Guards\TokenGuard
{

	/**
	 * The user provider implementation.
	 *
	 * @var
	 */
	protected $provider;

	/**
	 * Create a new token guard instance.
	 *
	 * @param \League\OAuth2\Server\ResourceServer $server
	 * @param  $provider
	 * @param \Laravel\Passport\TokenRepository $tokens
	 * @param \Laravel\Passport\ClientRepository $clients
	 * @param \Illuminate\Contracts\Encryption\Encrypter $encrypter
	 * @return void
	 */
	public function __construct(ResourceServer $server,
	                            $provider,
	                            TokenRepository $tokens,
	                            ClientRepository $clients,
	                            Encrypter $encrypter)
	{
		$this->server = $server;
		$this->tokens = $tokens;
		$this->clients = $clients;
		$this->provider = $provider;
		$this->encrypter = $encrypter;
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
			$psr = $this->server->validateAuthenticatedRequest($psr);

			// 获取Token对象
			$token = $this->tokens->find(
				$psr->getAttribute('oauth_access_token_id')
			);

			// If the access token is valid we will retrieve the user according to the user ID
			// associated with the token. We will use the provider implementation which may
			// be used to retrieve users from Eloquent. Next, we'll be ready to continue.
			$users = Auth::createUserProvider($this->provider);
			if (!$users) {
				return;
			}

			$user = $users->retrieveById(
				$psr->getAttribute('oauth_user_id')
			);

			// 用户找不到
			if (!$user) {
				return;
			}

			// Next, we will assign a token instance to this user which the developers may use
			// to determine if the token has a given scope, etc. This will be useful during
			// authorization such as within the developer's Laravel model policy classes.
			$clientId = $psr->getAttribute('oauth_client_id');

			// Finally, we will verify if the client that issued this token is still valid and
			// its tokens may still be used. If not, we will bail out since we don't want a
			// user to be able to send access tokens for deleted or revoked applications.
			if ($this->clients->revoked($clientId)) {
				return;
			}

			return $token ? $user->withAccessToken($token) : null;
		} catch (OAuthServerException $e) {
			return Container::getInstance()->make(
				ExceptionHandler::class
			)->report($e);
		}
	}
}