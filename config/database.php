<?php

return [

	/*
	|--------------------------------------------------------------------------
	| PDO Fetch Style
	|--------------------------------------------------------------------------
	|
	| By default, database results will be returned as instances of the PHP
	| stdClass object; however, you may desire to retrieve records in an
	| array format for simplicity. Here you can tweak the fetch style.
	|
	*/

	'fetch' => PDO::FETCH_ASSOC,

	/*
	|--------------------------------------------------------------------------
	| Default Database Connection Name
	|--------------------------------------------------------------------------
	|
	| Here you may specify which of the database connections below you wish
	| to use as your default connection for all database work. Of course
	| you may use many connections at once using the Database library.
	|
	*/

	'default' => 'mysql',

	/*
	|--------------------------------------------------------------------------
	| Database Connections
	|--------------------------------------------------------------------------
	|
	| Here are each of the database connections setup for your application.
	| Of course, examples of configuring each database platform that is
	| supported by Laravel is shown below to make development simple.
	|
	|
	| All database work in Laravel is done through the PHP PDO facilities
	| so make sure you have the driver for your particular database of
	| choice installed on your machine before you begin development.
	|
	*/

	'connections' => [

		'sqlite' => [
			'driver'   => 'sqlite',
			'database' => storage_path().'/database.sqlite',
			'prefix'   => '',
		],

		'mysql' => [
			'driver'    => 'mysql',
		    'read' => [
		        'host'      => env('DB_SLAVE_HOST', 'localhost'),
		    ],
		    'write' => [
		        'host'      => env('DB_HOST', 'localhost'),
		    ],
			'database'  => env('DB_DATABASE', 'data'),
			'username'  => env('DB_USERNAME', ''),
			'password'  => env('DB_PASSWORD', ''),
			'charset'   => 'utf8',
			'collation' => 'utf8_unicode_ci',
			'prefix'    => '',
			'strict'    => false,
            //'options'   => [PDO::ATTR_EMULATE_PREPARES => true,]

        ],
		'pgsql' => [
			'driver'   => 'pgsql',
			'host'     => env('DB_HOST', 'localhost'),
			'database' => env('DB_DATABASE', 'forge'),
			'username' => env('DB_USERNAME', 'forge'),
			'password' => env('DB_PASSWORD', ''),
			'charset'  => 'utf8',
			'prefix'   => '',
			'schema'   => 'public',
		],

		'sqlsrv' => [
			'driver'   => 'sqlsrv',
			'host'     => env('DB_HOST', 'localhost'),
			'database' => env('DB_DATABASE', 'forge'),
			'username' => env('DB_USERNAME', 'forge'),
			'password' => env('DB_PASSWORD', ''),
			'prefix'   => '',
		],
	    
	    'sqlserver' => [
	        'driver'   => 'sqlsrv',
	        'host'     => '10.16.1.110',
	        'port'     => 40000,
	        'database' => 'QPAccountsDB',
	        'username' => 'sa',
	        'password' => 888888,
	    
	    ],
	    'mongodb' => [
	        'driver'   => 'mongodb',
	        'host'     => ['10.16.10.57','10.16.10.58'],
// 	        'host'     => '192.168.199.131',
	        'port'     => 30000,
	        'database' => env('MDB_DATABASE', 'datatest'),
	        'username' => env('MDB_USERNAME', 'dataphp'),
	        'password' => env('MDB_PASSWORD', 'dataphp58'),
	        'options' => array(
	            'database' => env('MDB_DATABASE', 'datatest') // sets the authentication database required by mongo 3
	        )
	    ]
	],

	/*
	|--------------------------------------------------------------------------
	| Migration Repository Table
	|--------------------------------------------------------------------------
	|
	| This table keeps track of all the migrations that have already run for
	| your application. Using this information, we can determine which of
	| the migrations on disk haven't actually been run in the database.
	|
	*/

	'migrations' => 'migrations',

	/*
	|--------------------------------------------------------------------------
	| Redis Databases
	|--------------------------------------------------------------------------
	|
	| Redis is an open source, fast, and advanced key-value store that also
	| provides a richer set of commands than a typical key-value systems
	| such as APC or Memcached. Laravel makes it easy to dig right in.
	|
	*/

	'redis' => [

		'cluster' => false,

		'default' => [
			'host'     => env('REDIS_HOST', '139.217.234.127'),
            'password' => env('REDIS_PASSWORD', 'daliu.redis'),
			'port'     => 6379,
			'database' => 11,
		    'timeout' => 10,
		    'read_write_timeout' => 0,
		]
	],
    
    

];
