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
     * @param callable $error Slim's Error or PhpError handler.
     */
    public function __construct(Agent $newRelic, callable $error)
    {
        $this->newRelic = $newRelic;
        $this->error = $error;
    }

    /**
     * Invoke error handler
     *
     * @param ServerRequestInterface $request   The most recent Request object
     * @param ResponseInterface      $response  The most recent Response object
     * @param \Exception|\Throwable  $exception The caught Exception object, or Throwable objrct for PHP7
     *
     * @return ResponseInterface
     * @throws UnexpectedValueException
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $exception)
    {
        $this->newRelic->noticeError($exception->getMessage(), $exception);

        return $this->error->__invoke($request, $response, $exception);
    }
}