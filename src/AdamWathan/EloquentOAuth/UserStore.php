<?php namespace AdamWathan\EloquentOAuth;

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
        return $this->model->where('email', '=', $userDetails->getEmail())
            ->first();
    }
}
