<?php

namespace adrianfalleiro\PHPPM\Slim\Bootstrap;

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

class Slim implements ApplicationEnvironmentAwareInterface, RequestHandlerInterface
{
    protected $appenv;
    protected $debug = false;

    /**
     * @var App
     */
    protected $app;

    public function initialize($appenv, $debug)
    {

    }

    public function bootstrap($appBootstrap, $appenv, $debug)
    {
        $this->appenv = $appenv;
        $this->debug = $debug;

  
        $settings = require __DIR__ . '/../settings.php';


        $app = new App($settings);
        // require __DIR__ . '/../vendor/autoload.php';
        include __DIR__ . '/../dependencies.php';
        include __DIR__ . '/../routes.php';
  

        
        // include '../middlewares.php';

        $this->app = $app;
    }

    /**
     * Convert React\Http\Request to Slim\Http\Request
     *
     * @param ServerRequestInterface $psrRequest
     *
     * @return Slim\Http\Request $slimRequest
     */
    protected function mapRequest(ServerRequestInterface $psrRequest)
    {
        return new Request(
            $psrRequest->getMethod(),
            Uri::createFromString($psrRequest->getUri()),
            new Headers($psrRequest->getHeaders()),
            $psrRequest->getCookieParams(),
            $psrRequest->getServerParams(),
            $psrRequest->getBody(),
            $psrRequest->getUploadedFiles()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ServerRequestInterface $psrRequest)
    {
        $slimRequest = $this->mapRequest($psrRequest);

        $this->resetDefaultServices($slimRequest);

        return $this->app->process($slimRequest, new Response());
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
    protected function resetDefaultServices(Request $slimRequest)
    {
        $container = $this->app->getContainer();

        unset($container['request']);
        unset($container['environment']);

        // Reset app request instance
        $container['request'] = function($c) use ($slimRequest) {
            return $slimRequest;
        };

        // Reset app environment instance
        $container['environment'] = function($c) use ($slimRequest) {
            return new Environment($slimRequest->getServerParams());
        };

        // Reset cached route args
        $routes = $container['router']->getRoutes();
        foreach($routes as $route) {
            $route->setArguments([]);
        } 
    }
}