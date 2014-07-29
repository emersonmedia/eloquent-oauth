<?php namespace AdamWathan\EloquentOAuth;

use Illuminate\Support\ServiceProvider;
use Guzzle\Http\Client as HttpClient;

class EloquentOAuthServiceProvider extends ServiceProvider {

    protected $providerLookup = array(
        'facebook' => 'AdamWathan\\EloquentOAuth\\Providers\\FacebookProvider',
        'github' => 'AdamWathan\\EloquentOAuth\\Providers\\GitHubProvider',
        'google' => 'AdamWathan\\EloquentOAuth\\Providers\\GoogleProvider',
        'linkedin' => 'AdamWathan\\EloquentOAuth\\Providers\\LinkedInProvider',
    );

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->package('adamwathan/eloquent-oauth');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerOAuthManager();
    }

    protected function registerOAuthManager()
    {
        $this->app['adamwathan.oauth'] = $this->app->share(function($app)
    {
        $this->configureOAuthIdentitiesTable();
        $this->configureOAuthManager();
        $userStoreClass = $app['config']['eloquent-oauth::user-store'];
        $users = new $userStoreClass($app['config']['auth.model']);
        $stateManager = new StateManager($app['session.store'], $app['request']);
        $oauth = new OAuthManager($app['auth'], $app['redirect'], $stateManager, $users, new IdentityStore);
        $this->registerProviders($oauth);
        return $oauth;
    });
    }

    protected function registerProviders($oauth)
    {
        $providerAliases = $this->app['config']['eloquent-oauth::providers'];
        foreach ($providerAliases as $alias => $config) {
            if(isset($this->providerLookup[$alias])) {
                $providerClass = $this->providerLookup[$alias];
                $provider = new $providerClass($config, new HttpClient, $this->app['request']);
                $oauth->registerProvider($alias, $provider);
            }
        }
    }

    protected function configureOAuthIdentitiesTable()
    {
        OAuthIdentity::configureTable($this->app['config']['eloquent-oauth::table']);
    }

    /**
     * Configures the OAuthManager class
     *
     * @author diego <diego@emersonmedia.com>
     * @return void
     */
    protected function configureOAuthManager()
    {
        OAuthManager::configure([
            'app-user-not-found-behavior' => $this->app['config']['eloquent-oauth::app-user-not-found-behavior'],
        ]);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('adamwathan.oauth');
    }

}
