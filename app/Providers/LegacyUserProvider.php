<?php

namespace App\Providers;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;

class LegacyUserProvider extends EloquentUserProvider
{
    /**
     * Validate a user against the given credentials.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array  $credentials
     * @return bool
     */
    public function validateCredentials(UserContract $user, array $credentials)
    {
        $plain = $credentials['password'];

        // Legacy MD5 check
        // Check if the password in DB is MD5
        \Illuminate\Support\Facades\Log::info('LegacyUserProvider check', ['user' => $user->getAuthIdentifier(), 'db_pass' => $user->getAuthPassword(), 'plain_md5' => md5($plain)]);
        
        if (md5($plain) === $user->getAuthPassword()) {
            return true;
        }

        // Also fallback to default bcrypt in case we have new users or decided to rehash
        return parent::validateCredentials($user, $credentials);
    }
}
