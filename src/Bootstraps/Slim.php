<?php

namespace adrianfalleiro\PHPPM\Slim\Bootstraps;

use Interop\Http\Server\RequestHandlerInterface;
use PHPPM\Bootstraps\ApplicationEnvironmentAwareInterface;
use PHPPM\Bootstraps\BootstrapInterface;
use PHPPM\Bootstraps\HooksInterface;
use PHPPM\Bootstraps\RequestClassProviderInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use Slim\Container;
use Slim\Http\Response;
use Slim\Http\Request;
use Slim\Http\Uri;
use Slim\Http\Headers;
use Slim\Http\Environment;

class Slim implements ApplicationEnvironmentAwareInterface
{
    protected $appenv;
    protected $debug = false;

    /**
     * @var App
     */
    protected $app;

    public function initialize($appenv, $debug)
    {
        $this->appenv = $appenv;
        $this->debug = $debug;

        $this->app = $this->createSlimApp();
    }

    protected function createSlimApp()
    {
        require 'vendor/autoload.php';
        $settings = require './src/settings.php';

        $app = new App($settings);

        // Set up dependencies
        require './src/dependencies.php';

        // Register middleware
        require './src/middleware.php';

        // Register routes
        require './src/routes.php';

        return $app;
    }

    public function getApp()
    {
        return $this->app;
    }

    /**
     * Reset Pimple container bindings for certain
     * default services such as `request` and `environment`
     * so each request can operate cleanly
     * 
     * @param Slim\Http\Request $slimRequest
     * 
     * @return {void}
     */
    public function resetDefaultServices(Request $slimRequest)
    {
        $container = $this->app->getContainer();

        unset($container['request']);
        unset($container['environment']);

        // Reset app request instance
        $container['request'] = function ($c) use ($slimRequest) {
            return $slimRequest;
        };

        // Reset app environment instance
        $container['environment'] = function ($c) use ($slimRequest) {
            return new Environment($slimRequest->getServerParams());
        };

        // Reset cached route args
        $routes = $container['router']->getRoutes();
        foreach ($routes as $route) {
            $route->setArguments([]);
        } 
    }
}