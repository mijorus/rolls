<?php

return [
	'routes' => [
		['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
		['name' => 'page#index', 'url' => '/record', 'verb' => 'GET', 'postfix' => 'record'],
		['name' => 'page#index', 'url' => '/watch', 'verb' => 'GET', 'postfix' => 'watch'],
		['name' => 'page#index', 'url' => '/watch/{path}', 'verb' => 'GET', 'postfix' => 'watchroll'],

		['name' => 'rollapi#index', 'url' => '/api/hello', 'verb' => 'GET'],
		['name' => 'rollapi#get_upload_path', 'url' => '/api/upload_path', 'verb' => 'GET'],
		['name' => 'rollapi#create_roll', 'url' => '/api/rolls', 'verb' => 'POST'],
		['name' => 'rollapi#get_rolls',	'url' => '/api/rolls', 'verb' => 'GET'],
		['name' => 'rollapi#delete_roll',	'url' => '/api/rolls', 'verb' => 'DELETE'],
	],

];
