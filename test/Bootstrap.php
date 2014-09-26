<?php
namespace ABSCore\AuthenticationTest;

use Zend\Loader\StandardAutoloader;

error_reporting(E_ALL | E_STRICT);
chdir(__DIR__);

class Bootstrap
{
    public static function init()
    {
        $loader = new StandardAutoloader(array('autoregister_zf' => true));
        $loader->registerNamespace('ABSCore\Authentication', __DIR__.'/../src/');
        $loader->register();
    }
}

Bootstrap::init();
