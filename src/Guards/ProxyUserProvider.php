<?php

namespace ZhuiTech\BootLaravel\Guards;

use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use ZhuiTech\BootLaravel\Models\TokenUser;

/**
 * Class BackendUserProvider
 * @package App\Providers
 */
class ProxyUserProvider implements UserProvider
{
    /**
     * Retrieve a user by their unique identifier.
     *
     * @param mixed $identifier
     * @return TokenUser|Authenticatable|null
     */
    public function retrieveById($identifier): TokenUser|Authenticatable|null
    {
        $user = new TokenUser();
        $user->id = $identifier;
        $user->type = 'members';
        return $user;
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param mixed  $identifier
     * @param string $token
     * @return void
     */
    public function retrieveByToken($identifier, $token)
    {
        // TODO: Implement retrieveByToken() method.
    }

    /**
     * Update the "remember me" token for the given user in storage.
     *
     * @param Authenticatable $user
     * @param string          $token
     * @return void
     */
    public function updateRememberToken(Authenticatable $user, $token)
    {
        // TODO: Implement updateRememberToken() method.
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param array $credentials
     * @return void
     * @throws Exception
     */
    public function retrieveByCredentials(array $credentials)
    {
        // TODO: Implement updateRememberToken() method.
    }

    /**
     * Validate a user against the given credentials.
     *
     * @param Authenticatable $user
     * @param array           $credentials
     * @return bool
     * @throws Exception
     */
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        // TODO: Implement validateCredentials() method.
    }
}
