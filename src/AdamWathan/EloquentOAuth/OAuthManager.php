<?php namespace AdamWathan\EloquentOAuth;

use Closure;
use Illuminate\Auth\AuthManager as Auth;
use Illuminate\Routing\Redirector as Redirect;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use AdamWathan\EloquentOAuth\Exceptions\ProviderNotRegisteredException;
use AdamWathan\EloquentOAuth\Exceptions\InvalidAuthorizationCodeException;
use AdamWathan\EloquentOAuth\Providers\ProviderInterface;

class OAuthManager
{

    protected static $config = array(
        'app-user-not-found-behavior' => 'create',
    );

    protected $auth;
    protected $redirect;
    protected $stateManager;
    protected $users;
    protected $identities;
    protected $providers = array();


    public static function configure(array $config)
    {
        static::$config = $config;
    }


    public function __construct(Auth $auth, Redirect $redirect, StateManager $stateManager, UserStore $users, IdentityStore $identities)
    {
        $this->auth = $auth;
        $this->redirect = $redirect;
        $this->stateManager = $stateManager;
        $this->users = $users;
        $this->identities = $identities;
    }


    public function registerProvider($alias, ProviderInterface $provider)
    {
        $this->providers[$alias] = $provider;
    }

    public function authorize($provider)
    {
        $state = $this->generateState();
        $redirectTo = $this->getProvider($provider)->authorizeUrl($state);
        Log::info("OAuthManager::authorize() --> redirecting to: $redirectTo");
        return $this->redirect->to($redirectTo);
    }

    public function login($provider, Closure $callback = null)
    {
        $this->verifyState();
        $details = $this->getUserDetails($provider);
        $user = $this->getUser($provider, $details);
        Log::info("OAuthManager::login() --> User: " . print_r($user, true));
        if ($callback) {
            $callback($user, $details);
        }
        $this->updateUser($user, $provider, $details);
        $this->auth->login($user);
        return $user;
    }

    protected function generateState()
    {
        return $this->stateManager->generateState();
    }

    protected function getProvider($providerAlias)
    {
        if (! $this->hasProvider($providerAlias)) {
            throw new ProviderNotRegisteredException("No provider has been registered under the alias '{$providerAlias}'");
        }
        return $this->providers[$providerAlias];
    }

    protected function hasProvider($alias)
    {
        return isset($this->providers[$alias]);
    }

    protected function verifyState()
    {
        if (! $this->stateManager->verifyState()) {
            throw new InvalidAuthorizationCodeException;
        }
    }

    protected function getUserDetails($provider)
    {
        return $this->getProvider($provider)->getUserDetails();
    }

    protected function getUser($provider, $details)
    {
        if ($this->userExists($provider, $details)) {
            $user = $this->getExistingUser($provider, $details);
        } else {
            // User doesn't exists yet in webapp.
            // Take configured behaviour and proceed as needed.
            $behavior = self::$config['app-user-not-found-behavior'];
            switch ($behavior) {
                case 'fail':
                    // throw exception to be catched in webapp
                    throw new AppUserNotFoundException;
                    break;

                default:
                    // Create user
                    $user = $this->createUser();
                    break;
            }
        }
        return $user;
    }

    protected function updateUser($user, $provider, $details)
    {
        Log::info("OAuthManager::updateUser() --> User: " . print_r($user, true));
        $this->users->store($user);
        $this->updateAccessToken($user, $provider, $details);
    }

    protected function userExists($provider, ProviderUserDetails $details)
    {
        return (bool) $this->getExistingUser($provider, $details);
    }

    protected function getExistingUser($provider, $details)
    {
        $identity = $this->getIdentity($provider, $details);

        // A user might NOT exist in the identity table, but do exist in the
        // webapp users table (Because it was registered to the webapp directly
        // before using the social auth feature).
        if (is_null($identity))
        {
            // Check to see if user actually exist on webapp
            $appUser = $this->users->findInApp($details);
            if (is_null($appUser))
            {
                return null;
            }
            else
            {
                // Identity doesn't exists, but user is already created in
                // webapp. Create the identity now to sync with user
                // (this is done when login method calls $this->updateUser()).
                return $appUser;
            }
        }

    }

    protected function getIdentity($provider, ProviderUserDetails $details)
    {
        return $this->identities->getByProvider($provider, $details);
    }

    protected function createUser()
    {
        $user = $this->users->create();
        return $user;
    }

    protected function updateAccessToken($user, $provider, ProviderUserDetails $details)
    {
        $this->flushAccessTokens($user, $provider);
        $this->addAccessToken($user, $provider, $details);
    }

    protected function flushAccessTokens($user, $provider)
    {
        $this->identities->flush($user, $provider);
    }

    protected function addAccessToken($user, $provider, ProviderUserDetails $details)
    {

        Log::info("OAuthManager::addAccessToken() --> User: " . print_r($user, true));

        $identity = new OAuthIdentity;
        $identity->user_id = $user->getKey();
        $identity->provider = $provider;
        $identity->provider_user_id = $details->userId;
        $identity->access_token = $details->accessToken;
        $this->identities->store($identity);
    }


}
