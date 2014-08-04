<?php namespace AdamWathan\EloquentOAuth;

use Illuminate\Support\Facades\Log;

class UserStore
{
    protected $model;

    public function __construct($model)
    {
        $this->model = $model;
    }

    public function create()
    {
        $user = new $this->model;
        return $user;
    }

    public function store($user)
    {
        return $user->save();
    }

    public function findByIdentity($identity)
    {
        return $identity->belongsTo($this->model, 'user_id')->first();
    }

    /**
     * Searches the user in the webapp. Should return the user or null
     * if not found.
     * This default implementation searches by email, but this can be overriden
     * in the webapp, to use it's own search logic.
     *
     * @author diego <diego@emersonmedia.com>
     * @param  ProviderUserDetails $UserDetails [description]
     * @return User (webapp user class)
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
