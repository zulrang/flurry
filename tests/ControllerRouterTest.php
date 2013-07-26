<?php

namespace Flurry\Test\Controller {

	class Main {

		public static $_funcValue = 'FAILED';

		public function foo() {
			self::$_funcValue = 'SUCCESS';
		}
	}

	class testController {

		public static $_funcValue = 'FAILED';

		public function foo() {
			self::$_funcValue = 'SUCCESS';
		}
	}

}

namespace {

	class ControllerRouterTest extends PHPUnit_Framework_TestCase {

	    public function setUp()
	    {
	        //Remove environment mode if set
	        unset($_ENV['SLIM_MODE']);

	        //Reset session
	        $_SESSION = array();

	        //Prepare default environment variables
	        \Slim\Environment::mock(array(
	        	'REQUEST_METHOD' => 'GET',
	            'SCRIPT_NAME' => '/foo', //<-- Physical
	            'PATH_INFO' => '/bar', //<-- Virtual
	            'QUERY_STRING' => 'one=foo&two=bar',
	            'SERVER_NAME' => 'slimframework.com',
	        ));
	    }

		public function testWithConfig() {

			$router = new \Flurry\ControllerRouter(__DIR__.'/config');

			$this->assertInstanceOf('\\Flurry\\ControllerRouter', $router);
			$this->assertFalse($router->config['isDefault'], 'Expected non-default config');
			$this->assertEquals('\\Flurry\\Test\\Controller', $router->config['controllerNamespace']);
			$this->assertEquals('\\Flurry\\Test\\Model', $router->config['modelNamespace']);

		}

		public function testBlankConfig() {

			$router = new \Flurry\ControllerRouter(__DIR__.'/blank_config');

			$this->assertInstanceOf('\\Flurry\\ControllerRouter', $router);
			$this->assertTrue($router->config['isDefault'], 'Expected default config');

		}

		public function testNonexistentConfig() {

			$router = new \Flurry\ControllerRouter('idontexist');

			$this->assertInstanceOf('\\Flurry\\ControllerRouter', $router);
			$this->assertTrue($router->config['isDefault'], 'Expected default config');
			
		}

	    public function testAddRoutesWithDefaults() {

			$router = new \Flurry\ControllerRouter(__DIR__.'/config');

			$func = $router->addRoutes(array(
				'/bar' => 'foo'
			));

			$router->call();

			$this->assertEquals('SUCCESS', \Flurry\Test\Controller\Main::$_funcValue);

	    }


	}

}
