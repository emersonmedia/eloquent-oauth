<?php namespace AdamWathan\EloquentOAuth;

interface UserStoreInterface
{
    public function create();
    public function store($user);
    public function findByIdentity($identity);

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
    public function findInApp(ProviderUserDetails $userDetails);
}
