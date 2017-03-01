<?php

namespace Herloct\Slim;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\NotFoundException;
use Slim\Route;
use SobanVuex\NewRelic\Agent;

class NewRelicTransactionMiddleware
{
    const FALLBACK_NAME = '/index.php';

    /**
     * @var Agent
     */
    private $newRelic;

    /**
     * TransactionMiddleware constructor.
     *
     * @param Agent $newRelic New Relic Agent.
     */
    public function __construct(Agent $newRelic)
    {
        $this->newRelic = $newRelic;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param $next
     *
     * @return ResponseInterface
     * @throws NotFoundException
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        $name = $this->getTransactionName($request, $response);
        $this->newRelic->nameTransaction($name);

        if ($name === static::FALLBACK_NAME) {
            $e = new NotFoundException($request, $response);
            $this->newRelic->noticeError($e->getMessage(), $e);

            throw $e;
        }

        return $next($request, $response);
    }

    /**
     * Get transaction name to send to New Relic agent.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     *
     * @return string|null
     */
    protected function getTransactionName(ServerRequestInterface $request, ResponseInterface $response)
    {
        /* @var $route Route */
        $route = $request->getAttribute('route');
        if (empty($route)) {
            return static::FALLBACK_NAME;
        }

        return empty($route->getName()) ? $route->getPattern() : $route->getName();
    }
}
