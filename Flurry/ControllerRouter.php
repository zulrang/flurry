<?php

namespace Flurry;

class ControllerRouter extends \Slim\Slim {

    public $config;
    public $defaultConfig = array(
        'isDefault' => true,
        'controllerNamespace' => '\\Controllers'
    );

    public function __construct($configDir='config') {

        $configFilename = $configDir.'/app.php';

        // if configuration file exists, read into config member
        if(is_dir($configDir) && is_readable($configFilename)) {
            $this->config = require($configDir.'/app.php');

            // if no configuration data structure is supplied, use default
            if(!is_array($this->config)) {
                $this->config = $this->defaultConfig;
            } else {
                $this->config['isDefault'] = false;
            }
        } else {
            // no file, use default
            $this->config = $this->defaultConfig;
        }

        // if no controller namespace is supplied, use default
        if(!isset($this->config['controllerNamespace'])) {
            $this->config['controllerNamespace'] = 
                $this->defaultConfig['controllerNamespace'];
        }
        
        // create Slim object
        parent::__construct($this->config);

        // add session middleware
        $this->add(new \Slim\Middleware\SessionCookie());

        // add custom middleware
        $this->add(new \Flurry\Middleware\ContentNegotiation());
        $this->add(new \Flurry\Middleware\FieldSelectors());
    }

    public function addRoutes($routes) {

        foreach ($routes as $route => $path) {
     
            if (is_string($route)) {
                if (is_array($path)) {
                    foreach($path as $method => $action) {
                        $this->addRoute($route, $action . "@" . $method);
                    }
                } else {
                    $this->addRoute($route, $path);
                }
            } else {
                $this->addRoute($path[0], $path[1]);
            }
        }
    }

    protected function addRoute($route, $path) {

        $method = 'GET';
 
        if (strpos($path, "@") !== false) {
            list($path, $method) = explode("@", $path);
        }

        $func = $this->processCallback($path);
 
        $r = new \Slim\Route($route, $func);
        $r->setHttpMethods(strtoupper($method));
 
        $this->router->map($r);
    }

    protected function processCallback($path)
    {
        $class = "Main";
     
        if (strpos($path, ":") !== false) {
            list($class, $path) = explode(":", $path);
        }
     
        $function = ($path != "") ? $path : "index";
     
        $func = function () use ($class, $function) {
            $class = $this->config['controllerNamespace'] . "\\" . $class;
            $class = new $class($this);
     
            $args = func_get_args();
     
            return call_user_func_array(array($class, $function), $args);
        };
     
        return $func;
    }

    // overwrite render to allow content negotiation
    public function render($template, $data = array(), $status = null) {

        if (!is_null($status)) {
            $this->response->status($status);
        }

        // inspect response content type for json/xml
        $contentType = $this->response()->header('Content-Type');

        if($contentType == 'application/json') {
            // send json
            echo json_encode($data);
        } else if($contentType == 'application/xml') {
            // send XML
            echo Array2XML::createXML('response', $data)->saveXML();
        } else {
            // no specified type, render template
            $this->view->setTemplatesDirectory($this->config('templates.path'));
            $this->view->appendData($data);
            $this->view->display($template);
        }
    }


}
