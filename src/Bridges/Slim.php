<?php

namespace adrianfalleiro\PHPPM\Slim\Bridges;

use Interop\Http\Server\RequestHandlerInterface;
use PHPPM\Bootstraps\ApplicationEnvironmentAwareInterface;
use PHPPM\Bootstraps\BootstrapInterface;
use PHPPM\Bootstraps\HooksInterface;
use PHPPM\Bootstraps\RequestClassProviderInterface;
use Psr\Http\Message\ServerRequestInterface;
use PHPPM\Bridges\BridgeInterface;
use Slim\Http\Response;
use Slim\Http\Request;
use Slim\Http\Uri;
use Slim\Http\Headers;
use Slim\Http\Environment;
use adrianfalleiro\PHPPM\Slim\Bootstraps\Slim as SlimBootstrap;

class Slim implements BridgeInterface
{
    /**
     * An application implementing the HttpKernelInterface
     *
     * @var \Symfony\Component\HttpKernel\HttpKernelInterface
     */
    protected $application;

    /**
     * @var BootstrapInterface
     */
    protected $bootstrap;

    /**
     * @var string[]
     */
    protected $tempFiles = [];

    public function bootstrap($appBootstrap, $appenv, $debug)
    {
        
        $this->bootstrap = new SlimBootstrap();
        $this->bootstrap->initialize($appenv, $debug);
        $this->app = $this->bootstrap->getApp();
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

        $this->bootstrap->resetDefaultServices($slimRequest);

        return $this->app->process($slimRequest, new Response());
    }
}