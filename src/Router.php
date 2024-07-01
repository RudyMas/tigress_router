<?php

namespace Tigress;

use JetBrains\PhpStorm\NoReturn;

/**
 * Class Router (PHP version 8.3)
 * Build upon EasyRouter (rudymas/easyrouter)
 *
 * @author      Rudy Mas <rudy.mas@rudymas.be>
 * @copyright   2024, Rudy Mas (http://rudymas.be/)
 * @license     https://opensource.org/licenses/GPL-3.0 GNU General Public License, version 3 (GPL-3.0)
 * @version     1.0.0
 * @package     Tigress
 */
class Router
{
    /**
     * @var string $body
     * This contains the body of the request
     */
    private string $body = '';

    /**
     * @var string $default
     * The default route to be used
     */
    private string $default = '/';

    /**
     * @var array $parameters
     * This contains the URL stripped down to an array of parameters
     * Example: http://www.test.be/user/5
     * Becomes:
     * Array
     * {
     *     [0] => GET
     *     [1] => user
     *     [2] => 5
     * }
     */
    private array $parameters = [];

    /**
     * @var array $routes ;
     * This contains all the routes of the website
     * $routes[n]['request'] = the request method to check against
     * $routes[n]['path'] = the path to check against
     * $routes[n]['controller'] = the controller to load
     * $routes[n]['method'] = the method to call in the controller
     * $routes[n]['args'] = stdClass of argument(s) which you pass to the controller
     */
    private array $routes = [];

    /**
     * @var Core $Core
     * Needed for injecting Tigress_Core into Framework
     */
    private Core $Core;

    /**
     * Router constructor.
     *
     * @param Core $Core
     */
    public function __construct(Core $Core)
    {
        define('TIGRESS_ROUTER_VERSION', '1.0.0');

        $this->Core = $Core;
        $this->routes = $this->Core->Routes->routes;
        if (isset($this->Core->Routes->defaultRoute)) {
            $this->default = $this->Core->Routes->defaultRoute;
        }
        $this->body = file_get_contents('php://input');

        $this->processURL();
        $this->execute();
    }

    /**
     * Checks if a certain function exists on the server, or not.
     * I needed to add this, because else the router wouldn't work on a NGINX server!
     */
    private function checkFunctions(): void
    {
        if (!function_exists('apache_request_headers')) {
            function apache_request_headers(): array
            {
                $arh = [];
                $rx_http = '/\AHTTP_/';
                foreach ($_SERVER as $key => $val) {
                    if (preg_match($rx_http, $key)) {
                        $arh_key = preg_replace($rx_http, '', $key);
                        $rx_matches = explode('_', $arh_key);
                        if (count($rx_matches) > 0 and strlen($arh_key) > 2) {
                            foreach ($rx_matches as $ak_key => $ak_val) {
                                $rx_matches[$ak_key] = ucfirst($ak_val);
                            }
                            $arh_key = implode('-', $rx_matches);
                        }
                        $arh[$arh_key] = $val;
                    }
                }
                return ($arh);
            }
        }
    }

    /**
     * Check the routes and execute the correct controller
     *
     * @return void
     */
    private function execute(): void
    {
        $this->checkFunctions();
        if ($this->parameters[0] === 'OPTIONS') {
            $this->respondOnOptionsRequest(200);
        }
        $variables = [];
        foreach($this->routes as $route) {
            $testRoute = explode('/', $route->path);
            $testRoute[0] = $route->request;

            if (!(count($this->parameters) == count($testRoute))) {
                continue;
            }

            for ($x = 0; $x < count($testRoute); $x++) {
                if ($this->isItAVariable($testRoute[$x])) {
                    $key = trim($testRoute[$x], '{}');
                    $variables[$key] = str_replace('__', '/', $this->parameters[$x]);
                } elseif (strtolower($testRoute[$x]) != strtolower($this->parameters[$x])) {
                    continue 2;
                }
            }

            $variables['headers'] = apache_request_headers();
            $loadController = '\\Controller\\' . $route->controller;
            if ($route->method !== null) {
                $controller = new $loadController($this->Core);
                $arguments[] = $variables;
                $arguments[] = $this->body;
                call_user_func_array([$controller, $route->method], $arguments);
            } else {
                new $loadController($this->Core, $variables);
            }
            return;
        }
        header('Location: ' . $this->default);
        exit;
    }

    /**
     * function isItAVariable($input)
     * Checks if this part of the route is a variable
     *
     * @param string $input Part of the route to be tested
     * @return bool Return TRUE is a variable, FALSE if not
     */
    private function isItAVariable(string $input): bool
    {
        return preg_match("/^{(.+)}$/", $input);
    }

    /**
     * function processURL()
     * This will process the URL and extract the parameters from it.
     */
    private function processURL(): void
    {
        $defaultPath = '';
        $basePath = explode('?', urldecode($_SERVER['REQUEST_URI']));
        $requestURI = explode('/', rtrim($basePath[0], '/'));
        $requestURI[0] = strtoupper($_SERVER['REQUEST_METHOD']);
        $scriptName = explode('/', $_SERVER['SCRIPT_NAME']);
        $sizeofRequestURI = sizeof($requestURI);
        $sizeofScriptName = sizeof($scriptName);
        for ($x = 0; $x < $sizeofRequestURI && $x < $sizeofScriptName; $x++) {
            if (strtolower($requestURI[$x]) == strtolower($scriptName[$x])) {
                $defaultPath .= '/' . $requestURI[$x];
                unset($requestURI[$x]);
            }
        }
        $this->default = $defaultPath . $this->default;
        $this->parameters = array_values($requestURI);
    }

    /**
     * Send confirmation for an OPTIONS request
     *
     * @param int $httpResponseCode
     */
    #[NoReturn] private function respondOnOptionsRequest(int $httpResponseCode): void
    {
        http_response_code($httpResponseCode);
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: *');
        exit;
    }
}