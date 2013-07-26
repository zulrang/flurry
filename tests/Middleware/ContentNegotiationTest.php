<?php

class ContentNegotiationTestApp
{
    protected $environment;

    public function __construct()
    {
        $this->environment = \Slim\Environment::getInstance();
        $this->request = new \Slim\Http\Request($this->environment);
        $this->response = new \Slim\Http\Response();
    }

    public function environment() {
        return $this->environment;
    }

    public function response() {
        return $this->response;
    }

    public function call()
    {
        //Do nothing
    }
}

class ContentNegotiationTest extends PHPUnit_Framework_TestCase {

	public function testMiddlewareCall() {
		
		\Slim\Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'PATH_INFO' => "/app/item.json"
        ));
        $app = new ContentNegotiationTestApp();
        $mw = new \Flurry\Middleware\ContentNegotiation();
        $mw->setApplication($app);
        $mw->setNextMiddleware($app);
        $mw->call();

		$this->assertEquals('application/json', $app->response()->header('Content-Type'));
		$this->assertEquals("/app/item", $app->environment()['PATH_INFO']);

	}

    public function testMiddlewareCallWithoutType() {
        
        \Slim\Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'PATH_INFO' => "/app/model/id?querystring=foo"
        ));
        $app = new ContentNegotiationTestApp();
        $mw = new \Flurry\Middleware\ContentNegotiation();
        $mw->setApplication($app);
        $mw->setNextMiddleware($app);
        $mw->call();

        $this->assertEquals('text/html', $app->response()->header('Content-Type'));
        $this->assertEquals("/app/model/id?querystring=foo", $app->environment()['PATH_INFO']);

    }

    public function testMiddlewareCallWithExtraInfo() {
        
        \Slim\Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'PATH_INFO' => "/app/model/item.json?querystring=foo~:(field1,field2,field3)&otherrandominfo"
        ));
        $app = new ContentNegotiationTestApp();
        $mw = new \Flurry\Middleware\ContentNegotiation();
        $mw->setApplication($app);
        $mw->setNextMiddleware($app);
        $mw->call();

        $this->assertEquals('application/json', $app->response()->header('Content-Type'));
        $this->assertEquals("/app/model/item?querystring=foo~:(field1,field2,field3)&otherrandominfo", $app->environment()['PATH_INFO']);

    }

}
