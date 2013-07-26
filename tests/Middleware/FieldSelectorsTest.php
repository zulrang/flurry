<?php

class CustomAppMethod
{
    protected $environment;

    public function __construct()
    {
        $this->environment = \Slim\Environment::getInstance();
        $this->request = new \Slim\Http\Request($this->environment);
    }

    public function &environment() {
        return $this->environment;
    }

    public function call()
    {
        //Do nothing
    }
}

class FieldSelectorsTest extends PHPUnit_Framework_TestCase {

    public function testParsesFieldSelectors() {
        $uri = "/app/restcall?querystring=foo~:(field1,field2,field3)";
        $m = new \Flurry\Middleware\FieldSelectors();
        list($arr, $newuri) = $m->parseURI($uri);
        $this->assertEquals(['field1', 'field2', 'field3'], $arr);
        $this->assertEquals("/app/restcall?querystring=foo", $newuri);
    }

    public function testParsesFieldSelectorsExtra() {
        $uri = "/app/restcall?querystring=foo~:(field1,field2,field3)&otherrandominfo";
        $m = new \Flurry\Middleware\FieldSelectors();
        list($arr, $newuri) = $m->parseURI($uri);
        $this->assertEquals(['field1', 'field2', 'field3'], $arr);
        $this->assertEquals("/app/restcall?querystring=foo&otherrandominfo", $newuri);
    }

	public function testParsesNullFieldSelectors() {
		$uri = "/app/restcall?querystring=foo";
		$m = new \Flurry\Middleware\FieldSelectors();
		list($arr, $newuri) = $m->parseURI($uri);
		$this->assertEquals([], $arr);
		$this->assertEquals("/app/restcall?querystring=foo", $newuri);
	}

	public function testMiddlewareCall() {
		
		\Slim\Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'PATH_INFO' => "/app/restcall?querystring=foo~:(field1,field2,field3)"
        ));
        $app = new CustomAppMethod();
        $mw = new \Flurry\Middleware\FieldSelectors();
        $mw->setApplication($app);
        $mw->setNextMiddleware($app);
        $mw->call();

		$this->assertEquals(['field1', 'field2', 'field3'], $app->request->fieldSelectors);
		$this->assertEquals("/app/restcall?querystring=foo", $app->environment()['PATH_INFO']);

	}

    public function testMiddlewareCallWithoutFieldSelectors() {
        
        \Slim\Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'PATH_INFO' => "/app/restcall?querystring=foo"
        ));
        $app = new CustomAppMethod();
        $mw = new \Flurry\Middleware\FieldSelectors();
        $mw->setApplication($app);
        $mw->setNextMiddleware($app);
        $mw->call();

        $this->assertEquals("/app/restcall?querystring=foo", $app->environment()['PATH_INFO']);

    }

    public function testMiddlewareCallWithExtraInfo() {
        
        \Slim\Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'PATH_INFO' => "/app/restcall?querystring=foo~:(field1,field2,field3)&otherrandominfo"
        ));
        $app = new CustomAppMethod();
        $mw = new \Flurry\Middleware\FieldSelectors();
        $mw->setApplication($app);
        $mw->setNextMiddleware($app);
        $mw->call();

        $this->assertEquals("/app/restcall?querystring=foo&otherrandominfo", $app->environment()['PATH_INFO']);

    }

}
