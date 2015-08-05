<?php

// uncomment the following to define a path alias
// Yii::setPathOfAlias('local','path/to/local-folder');
// This is the main Web application configuration. Any writable
// CWebApplication properties can be configured here.
return array(
    'basePath' => dirname(__FILE__) . DIRECTORY_SEPARATOR . '..',
    'name' => 'PowerAdmin APIs',
    // preloading 'log' component
    'preload' => array('log'),
    // autoloading model and component classes
    'import' => array(
        'application.models.*',
        'application.components.*',
    ),
    'modules' => array(
        // uncomment the following to enable the Gii tool
        /*
        'gii' => array(
            'class' => 'system.gii.GiiModule',
            'password' => 'simo',
            // If removed, Gii defaults to localhost only. Edit carefully to taste.
            'ipFilters' => array('127.0.0.1', '::1'),
        ),
        */
    ),
    // application components
    'components' => array(
        'user' => array(
            // enable cookie-based authentication
            'allowAutoLogin' => true,
        ),
        // uncomment the following to enable URLs in path-format
        'urlManager' => array(
            'urlFormat' => 'path',
            'showScriptName' => false,
            'rules' => array(
                '/' => 'api/index',
                '<controller:\w+>/<id:\d+>' => '<controller>/view',
                '<controller:\w+>/<action:\w+>/<id:\d+>' => '<controller>/<action>',
                '<controller:\w+>/<action:\w+>' => '<controller>/<action>',
            ),
        ),
        'db' => array(
            'connectionString' => 'mysql:host='.getenv('MYSQL_HOST').';dbname='.getenv('MYSQL_DBNAME'),
            'emulatePrepare' => true,
            'username' => getenv('MYSQL_USER'),
            'password' => getenv('MYSQL_PASS'),
            'charset' => 'utf8',
        ),
        'errorHandler' => array(
            'errorAction' => 'api/error',
        ),
        'log' => array(
            'class' => 'CLogRouter',
            'routes' => array(
                array(
                    'class' => 'CFileLogRoute',
                    'levels' => 'error, warning',
                ),
            // uncomment the following to show log messages on web pages
            /*
              array(
              'class'=>'CWebLogRoute',
              ),
             */
            ),
        ),
    ),
    // application-level parameters that can be accessed
    // using Yii::app()->params['paramName']
    'params' => array(
        // this is used in contact page
        'adminEmail' => 'webmaster@swite.com',
        'soa_defaults' =>
        array(
            'name_server' => 'ns1.swite.com',
            'email_address' => 'hostmaster.swite.com',
            'refresh_time' => '43200',
            'retry_time' => '900',
            'expiry_time' => '1209600',
            'nx_time' => '10800',
        ),
        
    ),
);