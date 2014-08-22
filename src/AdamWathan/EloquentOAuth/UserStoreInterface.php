<?php namespace AdamWathan\EloquentOAuth;

/**
 * This interface defines the needed methods that specifc UserStore class
 * must implement
 *
 * @author Diego Caprioli <diego@emersonmedia.com>
 */
interface UserStoreInterface
{

    /**
     * Creates and returns a new User model instance
     *
     * @return The created User instance
     */
    public function create();

    /**
     * Saves the user
     *
     * @param $user The user instance to save
     * @return bool True if model was saved, false otherwise.
     */
    public function store($user);

    /**
     * Returns the user that corresponds to the requested identity
     *
     * @param  OAuthIdentity $identity
     * @return The user instace
     */
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
