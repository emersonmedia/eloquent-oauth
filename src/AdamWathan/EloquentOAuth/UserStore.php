<?php namespace AdamWathan\EloquentOAuth;

use Illuminate\Support\Facades\Log;

/**
 * Provides basic access and manipulation for user objects
 *
 * @todo: the methods of this class can be enforced by implementing an interface
 * and making any user defined "UserStore" to implement that interface in order
 * to be used by this library. Then, the config value 'user-store', should be
 * a class that implement the interface
 */
class UserStore implements UserStoreInterface
{
    protected $model;

    public function __construct($model)
    {
        $this->model = $model;
    }

    /**
     * @see UserStoreInterface::create()
     */
    public function create()
    {
        $user = new $this->model;
        return $user;
    }

    /**
     * @see UserStoreInterface::store()
     */
    public function store($user)
    {
        return $user->save();
    }

    /**
     * @see UserStoreInterface::findByIdentity
     */
    public function findByIdentity($identity)
    {
        return $identity->belongsTo($this->model, 'user_id')->first();
    }

    /**
     * @see \AdamWathan\EloquentOAuth\UserStoreInterface::findInApp
     */
    public function findInApp(ProviderUserDetails $userDetails)
    {
        Log::info("UserStore::findInApp() --> UserDetails: " . print_r($userDetails, true));
        $user = new $this->model;
        $user = $user->where('email', '=', $userDetails->email)
            ->first();
        Log::info("UserStore::findInApp() --> User: " . print_r($user, true));
        return $user;
    }
}
