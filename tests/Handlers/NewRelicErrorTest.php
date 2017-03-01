<?php

namespace Herloct\Slim\Handlers;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Slim\Handlers\Error;
use Slim\Http\Environment;
use Slim\Http\Request;
use Slim\Http\Response;
use SobanVuex\NewRelic\Agent;

class NewRelicErrorTest extends TestCase
{
    /**
     * @return ObjectProphecy
     */
    private function getAgent()
    {
        return $this->prophesize(Agent::class);
    }

    public function testThrowException()
    {
        $actualMessage = null;
        $actualException = null;

        $agent = $this->getAgent();
        $agent->noticeError(Argument::type('string'), Argument::type(\Exception::class))
            ->will(function ($args) use (&$actualMessage, &$actualException) {
                $actualMessage = $args[0];
                $actualException = $args[1];
            });

        $env = Environment::mock();
        $request = Request::createFromEnvironment($env);

        $exception = new \InvalidArgumentException('Please use another name.');

        $errorHandler = $this->prophesize(Error::class);
        $errorHandler->__invoke();

        $handler = new NewRelicError($agent->reveal(), $errorHandler->reveal());
        $handler->__invoke($request, new Response(), $exception);

        $this->assertEquals($exception->getMessage(), $actualMessage);
        $this->assertEquals($exception, $actualException);
    }
}
