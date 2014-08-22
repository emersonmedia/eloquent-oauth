<?php

return array(
	'table' => 'oauth_identities',
	'providers' => array(
		'facebook' => array(
			'id' => '12345678',
			'secret' => 'y0ur53cr374ppk3y',
			'redirect' => URL::to('your/facebook/redirect'),
			'scope' => array(),
		),
		'google' => array(
			'id' => '12345678',
			'secret' => 'y0ur53cr374ppk3y',
			'redirect' => URL::to('your/google/redirect'),
			'scope' => array(),
		),
		'github' => array(
			'id' => '12345678',
			'secret' => 'y0ur53cr374ppk3y',
			'redirect' => URL::to('your/github/redirect'),
			'scope' => array(),
		),
		'linkedin' => array(
			'id' => '12345678',
			'secret' => 'y0ur53cr374ppk3y',
			'redirect' => URL::to('your/linkedin/redirect'),
			'scope' => array(),
		),
	),

    /*
    |--------------------------------------------------------------------------
    | user-not-exists-behaviour
    |--------------------------------------------------------------------------
    |
    | Config option that indicates the behaviour of OAuthManger when a user
    | doesn't exists yet in the webapp. The configuration options available are:
    | * 'create': If the user doesn't exist, it will create a new user for the
    |       webapp.
    | * 'fail': If the user doesn't exist, it will throw an
    |       AppUserNotFoundException, that the app can catch and manage as
    |       needed.
    |
    */
    'app-user-not-found-behavior' => 'create',

);
