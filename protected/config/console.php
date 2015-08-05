<?php

// This is the configuration for yiic console application.
// Any writable CConsoleApplication properties can be configured here.
return array(
	'basePath'=>dirname(__FILE__).DIRECTORY_SEPARATOR.'..',
	'name'=>'My Console Application',
        'import' => array(
                'application.models.*',
                'application.components.*',
            ),
	// preloading 'log' component
	'preload'=>array('log'),

	// application components
	'components'=>array(
		'db' => array(
            'connectionString' => 'mysql:host='.getenv('MYSQL_HOST').';dbname='.getenv('MYSQL_DBNAME'),
            'emulatePrepare' => true,
            'username' => getenv('MYSQL_USER'),
            'password' => getenv('MYSQL_PASS'),
            'charset' => 'utf8',
        ),
		'log'=>array(
			'class'=>'CLogRouter',
			'routes'=>array(
				array(
					'class'=>'CFileLogRoute',
					'levels'=>'error, warning',
				),
			),
		),
	),
);