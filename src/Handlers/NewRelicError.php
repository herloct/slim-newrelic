<?php

namespace Herloct\Slim\Handlers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use SobanVuex\NewRelic\Agent;
use UnexpectedValueException;

class NewRelicError
{
    /**
     * @var Agent
     */
    private $newRelic;

    /**
     * @var callable
     */
    private $error;

    /**
     * NewRelicError constructor.
     *
     * @param Agent $newRelic New Relic Agent.
     * @param callable $error Slim's Error handler.
     */
    public function __construct(Agent $newRelic, callable $error)
    {
        $this->newRelic = $newRelic;
        $this->error = $error;
    }

    /**
     * Invoke error handler
     *
     * @param ServerRequestInterface $request The most recent Request object
     * @param ResponseInterface $response The most recent Response object
     * @param \Exception $exception The caught Exception object
     *
     * @return ResponseInterface
     * @throws UnexpectedValueException
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, \Exception $exception)
    {
        $this->newRelic->noticeError($exception->getMessage(), $exception);

        return $this->error->__invoke($request, $response, $exception);
    }
}
