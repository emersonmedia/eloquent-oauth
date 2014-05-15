<?php

return array(
	'table' => 'oauth_identities',
	'providers' => array(
		'facebook' => array(
			'id' => '12345678',
			'secret' => 'y0ur53cr374ppk3y',
			'redirect' => URL::to('your/facebook/redirect'),
			'scope' => array(),
		)
	),

    /*
    |--------------------------------------------------------------------------
    | exception-on-user-not-exits
    |--------------------------------------------------------------------------
    |
    | Boolean flag that indicates that an exception should be thrwon if the user
    | doesn't exists in the application. This might be useful for apps that have 
    | a business flow that requires previous account creation to control who can
    | actually login.
    |
    */    
    'exception-on-user-not-exits' => true,
    
);
