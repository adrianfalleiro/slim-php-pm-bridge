<?php

namespace adrianfalleiro\PHPPM\Slim\Bridges;

use Interop\Http\Server\RequestHandlerInterface;
use PHPPM\Bootstraps\ApplicationEnvironmentAwareInterface;
use PHPPM\Bootstraps\BootstrapInterface;
use PHPPM\Bootstraps\HooksInterface;
use PHPPM\Bootstraps\RequestClassProviderInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
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
        $_COOKIE = [];

        foreach ($psrRequest->getHeader('Cookie') as $cookieHeader) {
            $cookies = explode(';', $cookieHeader);
            foreach ($cookies as $cookie) {
                if (strpos($cookie, '=') == false) {
                    continue;
                }
                list($name, $value) = explode('=', trim($cookie));
                $_COOKIE[$name] = $value;
                if ($name === session_name()) {
                    session_id($value);
                }
            }
        }

        session_start();

        
        ob_start();
        var_dump($_SESSION);
        file_put_contents('php://stderr', ob_get_clean() . PHP_EOL, FILE_APPEND);

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

    protected function mapResponse(ResponseInterface $slimResponse)
    {
        $nativeHeaders = [];
        foreach (headers_list() as $header) {
            if (false !== $pos = strpos($header, ':')) {
                $name = substr($header, 0, $pos);
                $value = trim(substr($header, $pos + 1));
                if (isset($nativeHeaders[$name])) {
                    if (!is_array($nativeHeaders[$name])) {
                        $nativeHeaders[$name] = [$nativeHeaders[$name]];
                    }
                    $nativeHeaders[$name][] = $value;
                } else {
                    $nativeHeaders[$name] = $value;
                }
            }
        }

        if (PHP_SESSION_ACTIVE === session_status()) {
            // make sure open session are saved to the storage
            // in case the framework hasn't closed it correctly.
            session_write_close();
        }
        
        // reset session_id in any case to something not valid, for next request
        session_id('');
                
        // reset $_SESSION
        session_unset();
        unset($_SESSION);

        header_remove();

        $headers = array_merge($nativeHeaders, $slimResponse->getHeaders());

        ob_start();
        var_dump($_SESSION);
        file_put_contents('php://stderr', ob_get_clean() . PHP_EOL, FILE_APPEND);

        return new Response(
            $slimResponse->getStatusCode(),
            new Headers($headers),
            $slimResponse->getBody()
        );


    }

    /**
     * {@inheritdoc}
     */
    public function handle(ServerRequestInterface $psrRequest)
    {
        $slimRequest = $this->mapRequest($psrRequest);

        $this->bootstrap->resetDefaultServices($slimRequest);

        $slimResponse = $this->app->process($slimRequest, new Response());

        return $this->mapResponse($slimResponse);
    }
}