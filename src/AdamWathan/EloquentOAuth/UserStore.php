<?php namespace AdamWathan\EloquentOAuth;

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
}
