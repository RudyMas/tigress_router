<?php

namespace Tigress;

use Tigress\Core;

/**
 * Class Router (PHP version 7.2)
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
     * @var string $body
     * This contains the body of the request
     */
    private string $body = '';

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
     * @var string $default
     * The default route to be used
     */
    private string $default = '/';

    /**
     * @var \Tigress\Core $Core
     * Needed for injecting Tigress_Core into Framework
     */
    private Core $Core;

    /**
     * Router constructor.
     *
     * @param \Tigress\Core $Core
     */
    public function __construct(Core $Core)
    {
        define('TIGRESS_ROUTER_VERSION', '1.0.0');

        $this->Core = $Core;
        $this->routes = $this->Core->Routes->routes;
        $this->body = file_get_contents('php://input');

        print('<pre>');
        print_r($this->routes);
        print('</pre>');
    }
}