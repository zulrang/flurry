<?php

namespace Flurry;

use Slim\Slim;

class Controller {

    protected $models;
    protected $dbManager;
 
    public function __construct($app)
    {
        $this->app = $app;
    }

    // Passthrough functions
    public function view() {
        return $this->app->view();
    }

    public function request() {
        return $this->app->request();
    }

    /**
     * Set flash message for subsequent request
     * @param  string   $key
     * @param  mixed    $value
     */
    public function flash($key, $value)
    {
        $this->app->flash($key, $value);
    }

    /**
     * Set flash message for current request
     * @param  string   $key
     * @param  mixed    $value
     */
    public function flashNow($key, $value)
    {
        $this->app->flashNow($key, $value);
    }

    /**
     * Keep flash messages from previous request for subsequent request
     */
    public function flashKeep()
    {
        $this->app->flashKeep();
    }


    public function model($name) {
        if(!isset($this->models[$name])) {
            $className = $this->app->config['modelNamespace'] . '\\' . $name;
            $this->models[$name] = new $className($this->dbManager);
        }
        return $this->models[$name];
    }

    public function render($name, $data = array(), $status = null)
    {
        $this->view()->appendData(array('app_base' => $this->request()->getRootUri()));
        if (strpos($name, ".html") === false) {
            $name = $name . ".html";
        }
        $this->app->render($name, $data, $status);
    }

}
