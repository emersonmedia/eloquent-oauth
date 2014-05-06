<?php namespace AdamWathan\EloquentOAuth;

use Closure;
use Illuminate\Auth\AuthManager as Auth;
use Illuminate\Routing\Redirector as Redirect;
use Illuminate\Session\Store as Session;
use Illuminate\Support\Facades\Log;

use AdamWathan\EloquentOAuth\Providers\ProviderInterface;

class OAuthManager
{
    protected $auth;
    protected $model;
    protected $redirect;
    protected $session;
    protected $identities;
    protected $state;
    protected $providers = array();

    public function __construct(Auth $auth, $model, Redirect $redirect, Session $session, IdentityRepository $identities)
    {
        $this->auth = $auth;
        $this->model = $model;
        $this->redirect = $redirect;
        $this->session = $session;
        $this->identities = $identities;
    }

    public function registerProvider($alias, ProviderInterface $provider)
    {
        $this->providers[$alias] = $provider;
    }

    public function authorize($provider)
    {
        $state = $this->generateState();
        return $this->redirect->to($this->getProvider($provider)->authorizeUrl($state));
    }

    protected function generateState()
    {
        $this->setState($state = str_random());
        return $state;
    }

    protected function setState($state)
    {
        $this->session->put('oauth.state', $state);
    }

    protected function getState()
    {
        return $this->session->get('oauth.state');
    }

    protected function getProvider($providerAlias)
    {
        return $this->providers[$providerAlias];
    }

    public function login($provider, Closure $callback = null)
    {
        $this->verifyState();
        $details = $this->getUserDetails($provider);

        Log::info("OAuthManager->login():: user details: " . var_export($details, true));

        $user = $this->getUser($provider, $details);
        if ($callback) {
            $callback($user, $details);
        }
        $this->auth->login($user);
    }

    protected function verifyState()
    {
        if (! isset($_GET['state']) || $_GET['state'] !== $this->getState()) {
            throw new InvalidAuthorizationCodeException("this->getState() = " . $this->getState() . "  -  GET[state] = " . $_GET['state']);
        }
    }

    protected function getUser($provider, $details)
    {
        if ($this->userExists($provider, $details)) {
            $user = $this->updateUser($provider, $details);
        } else {
            $user = $this->createUser($provider, $details);
        }
        return $user;
    }

    protected function getUserDetails($provider)
    {
        return $this->getProvider($provider)->getUserDetails();
    }

    protected function userExists($provider, ProviderUserDetails $details)
    {

        //look for user in Identities records
        $identity = $this->getIdentity($provider, $details);

        if (!$identity) 
        {
            //there's no identity for the user
            //search it in the app users, as it might be already created there
            //as an app user, and not with oauth.            
            $userModel = new $this->model;
            $user = $userModel->byEloquentOAuthUserDetails($details)->first();
            if ($user)
            {
                //user exists in app, sync Identity
                $this->addAccessToken($user, $provider, $details);
            }
            else
            {
                //user doesn't exist in app either
                return false;
            }
        }

        return true;

    }

    protected function getIdentity($provider, ProviderUserDetails $details)
    {
        /* This method call would only get the user if exist in the OAuthIdentites table. 
         * If the user already existed in the users table before, because he registered
         * using the website own account, this still wouldn't find the user's identity.
         * This creates a problem, because the login would then try to create the user,
         * and maybe failing some steps later with duplicated email address, or what is 
         * worst, not failing and duplicating users records.
         *
         * TODO: upgrade code to take this into consideration and if the user previously 
         * existed in the website but no record is registered in the OAuthIdentity, sync them.
         * This might be accomplished by also providing a callback method in the User class, that
         * takes the usersDetails object from the oauthlibrary, searches the website users, and
         * returns whichs user IS the one provided (for sure querying by email address, but in this
         * way we can allow the implementing site decide how). If no user is found to be the same, 
         * it can return null (and now the user could be created safely)
         *
         * Done in method userExists. See above.
         * 
         */
        
        return $this->identities->getByProvider($provider, $details->userId);
    }

    protected function updateUser($provider, ProviderUserDetails $details)
    {
        $identity = $this->getIdentity($provider, $details);
        $user = $identity->belongsTo($this->model, 'user_id')->first();
        $this->updateAccessToken($user, $provider, $details);
        return $user;
    }

    protected function createUser($provider, ProviderUserDetails $details)
    {
        $user = new $this->model;
        if (!$user->saveForEloquentOAuth($details)) //this callback should be enforced by an interface
        {
            Log::info("OAuthManager:: User not saved. Errors: " . var_export($user->errors(), true));
            throw new Exception();
        }
        $this->addAccessToken($user, $provider, $details);
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
        $identity = new OAuthIdentity;
        
        //This is really the app user's user_id... so why name it key? This assumes that the
        //user object in the app has the method "getKey()" returning objectId, this is not
        //always like that. Better ask it with another method name and enforce it by an interface also.
        
        //$identity->user_id = $user->getKey();
        $identity->user_id = $user->getIdForEloquentOAuth(); //this callback should be enforced by an interface

        $identity->provider = $provider;
        $identity->provider_user_id = $details->userId;
        $identity->access_token = $details->accessToken;
        $this->identities->store($identity);
    }
}
