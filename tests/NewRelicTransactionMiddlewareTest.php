<?php

namespace Herloct\Slim;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Slim\App;
use Slim\Exception\NotFoundException;
use Slim\Http\Environment;
use Slim\Http\Request;
use Slim\Http\Response;
use SobanVuex\NewRelic\Agent;

class NewRelicTransactionMiddlewareTest extends TestCase
{
    /**
     * @return ObjectProphecy
     */
    private function getAgent()
    {
        return $this->prophesize(Agent::class);
    }

    /**
     * @return App
     */
    private function getApplication(ObjectProphecy $agent)
    {
        $app = new App([
            'settings' => [
                'determineRouteBeforeAppMiddleware' => true
            ],

            NewRelicTransactionMiddleware::class => function ($c) use ($agent) {
                return new NewRelicTransactionMiddleware($agent->reveal());
            }
        ]);
        $app->add(NewRelicTransactionMiddleware::class);

        return $app;
    }

    public function testNamedRouteTransactionNames()
    {
        $name = 'say_hello';
        $actual = null;

        $agent = $this->getAgent();
        $agent->nameTransaction(Argument::type('string'))->will(function ($args) use (&$actual) {
            $actual = $args[0];
        });

        $app = $this->getApplication($agent);
        $app->get('/hello/{name}', function ($request, $response, $args) {
            return $response->write("Hello " . $args['name']);
        })->setName('say_hello');

        $env = Environment::mock([
            'REQUEST_URI' => '/hello/herloct'
        ]);
        $request = Request::createFromEnvironment($env);
        $response = $app->process($request, new Response());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($name, $actual);
    }

    public function testPatternRouteTransactionNames()
    {
        $pattern = '/hello/{name}';
        $actual = null;

        $agent = $this->getAgent();
        $agent->nameTransaction(Argument::type('string'))->will(function ($args) use (&$actual) {
            $actual = $args[0];
        });

        $app = $this->getApplication($agent);
        $app->get($pattern, function ($request, $response, $args) {
            return $response->write("Hello " . $args['name']);
        });

        $env = Environment::mock([
            'REQUEST_URI' => '/hello/herloct'
        ]);
        $request = Request::createFromEnvironment($env);
        $response = $app->process($request, new Response());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($pattern, $actual);
    }

    public function testNotFoundAndFallbackTransactionNames()
    {
        $actual = null;
        $exceptionThrown = false;

        $agent = $this->getAgent();
        $agent->noticeError(Argument::type('string'), Argument::type(NotFoundException::class))->will(function ($args) use (&$exceptionThrown) {
            $exceptionThrown = true;
        });
        $agent->nameTransaction(Argument::type('string'))->will(function ($args) use (&$actual) {
            $actual = $args[0];
        });

        $app = $this->getApplication($agent);
        $app->get('/hello/{name}', function ($request, $response, $args) {
            return $response->write("Hello " . $args['name']);
        });

        $env = Environment::mock([
            'REQUEST_URI' => '/'
        ]);
        $request = Request::createFromEnvironment($env);
        $response = $app->process($request, new Response());

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('/index.php', $actual);
        $this->assertTrue($exceptionThrown);
    }
}