<?php
/**
 * Flurry - A microframework based on Slim
 *
 * @author      Josh Lockhart <info@flurryframework.com>
 * @copyright   2013 Roger Collins
 * @link        http://www.flurryframework.com
 * @license     http://www.flurryframework.com/license
 * @version     0.9.0
 * @package     Flurry
 *
 */
namespace Flurry\Middleware;

 /**
  * ContentNegotiation
  *
  * This is middleware for a Slim application that allows the user
  * to specify the requested content return type as a file extension
  * added to the URI
  *
  * @package    Flurry
  * @author     Roger Collins
  * @since      0.9.0
  */
class ContentNegotiation extends \Slim\Middleware
{
    /**
     * @var array
     */
    protected $settings;
    protected $contentTypes = [
                'json' => 'application/json',
                'xml' => 'application/xml'
            ];

    /**
     * Constructor
     * @param  \Slim  $app
     * @param  array  $settings
     */
    public function __construct($settings = array())
    {
        $this->settings = $settings;
    }

    /**
     * Call
     *
     * Implements Slim middleware interface. This method is invoked and passed
     * an array of environment variables. This middleware inspects the environment
     * variables for the path extension; if found, this middleware modifies the 
     * environment settings so downstream middleware and/or the Slim
     * application will treat the request with the desired content type.
     *
     * @param  array         $env
     * @return array[status, header, body]
     */
    public function call()
    {
        $env = $this->app->environment();
        $res = $this->app->response();

        // extension content negotiation
        $pathInfo = $env['PATH_INFO'];

        if(preg_match('/\.(json|xml)/', $pathInfo, $matches)) {
            $responseType = $this->contentTypes[$matches[1]];
            $env['PATH_INFO'] = preg_replace('/\.(json|xml)/', '', $pathInfo);

            // update response header
            $res->header('Content-Type', $responseType);
        }

        $this->next->call();
    }
}
