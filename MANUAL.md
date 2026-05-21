# Tigress Router — Programmer's Manual

**Version:** 2025.12.09 | **Package:** `tigress/router` | **PHP:** >= 8.5 | **License:** GPL-3.0

---

## Overview

The Tigress Router maps incoming HTTP requests to controller classes. It reads route definitions from a JSON config file, matches the request URI against registered patterns (including dynamic parameters), and dispatches to the appropriate controller method.

---

## Installation

```bash
composer create-project tigress/tigress <project_name>
```

The Router is automatically loaded by the Tigress Core module. Autoloading follows PSR-4 under the `Tigress\` namespace:

```json
{
  "autoload": {
    "psr-4": {
      "Tigress\\": "src/"
    }
  }
}
```

---

## Configuration

Routes are defined in **`config/routes.json`** at the project root.

### Basic Structure

```json
{
  "routes": [
    {
      "request": "GET",
      "path": "/",
      "controller": "HomeController",
      "method": "index"
    },
    {
      "request": "GET",
      "path": "/user/{id}",
      "controller": "UserController",
      "method": "show"
    }
  ]
}
```

Each route entry has these fields:

| Field        | Type     | Required | Description |
|-------------|----------|----------|-------------|
| `request`   | string   | yes      | HTTP method: `GET`, `POST`, `PUT`, `PATCH`, `DELETE`, `OPTIONS` |
| `path`      | string   | yes      | URL path, may contain `{variable}` placeholders |
| `controller`| string   | yes      | Class name **without namespace** — `\Controller\` is prepended |
| `method`    | string   | no       | Method to call on the controller. If omitted, the controller is used as a callable class (`new $controller($variables)`) |

### Extra Routes from Composer Packages

Routes from vendor packages are supported via the `extraRoutes` key:

```json
{
  "routes": [...],
  "extraRoutes": [
    {
      "package": "vendor/package-name"
    }
  ],
  "defaultRoute": "/home"
}
```

The Router will look for `config/routes.json` inside `vendor/<package>/` and merge those routes with the application's own routes.

### Default Route

The `defaultRoute` key specifies a fallback path. When no route matches, the client is redirected there via a `Location` header.

---

## Route Matching

### Static Routes

```json
{ "request": "GET", "path": "/about", "controller": "PageController", "method": "about" }
```

The path `/about` must match exactly (case-insensitive).

### Dynamic (Variable) Routes

```json
{ "request": "GET", "path": "/user/{id}", "controller": "UserController", "method": "show" }
```

URL segments wrapped in `{curly braces}` are captured as variables and passed to the controller.

### Encoded Slashes in Variables

Forward slashes inside a variable value can be encoded as `__` (double underscore). The router decodes `__` back to `/` before passing the value to the controller.

- URL path: `/file/some__nested__path` → variable value: `some/nested/path`

### Request Methods

The first element of the internal parameters array is always the **uppercased HTTP method** (from `$_SERVER['REQUEST_METHOD']`). The router checks this against the `request` field of each route.

### OPTIONS Requests (CORS)

An `OPTIONS` request immediately returns HTTP 200 with:

```
Access-Control-Allow-Origin: *
Access-Control-Allow-Headers: *
Content-Type: application/json
```

No route matching is performed for `OPTIONS`.

### No-Match Behavior

If no route matches, the client is redirected to the `defaultRoute` (or the default prefix path + `/`).

---

## Controller Interface

### When `method` IS defined

The controller is instantiated, then the named method is called with two arguments:

```php
namespace Controller;

class UserController
{
    public function show(array $variables, string $body): void
    {
        $id = $variables['id'];
        $headers = $variables['headers']; // all request headers
        // ...
    }
}
```

**Arguments passed:**

| Index | Type     | Content |
|-------|----------|---------|
| 0     | array    | Associative array with: route variables, `headers` (all request headers), and any other matched params |
| 1     | string   | Raw request body (`php://input`) |

### When `method` is NOT defined

The controller class is used as a callable class — its constructor receives the variables array:

```php
namespace Controller;

class HomeController
{
    public function __construct(array $variables)
    {
        // $variables['headers'] contains all request headers
    }
}
```

---

## Public API

### `Router::version(): string`

Returns the router version string (`2025.12.09`).

### `new Router()`

Constructor reads the raw request body from `php://input` into `$this->body`.

### `$router->createRoutes(): array`

Loads and returns all routes from:
1. `config/routes.json` (required)
2. External package routes listed under `extraRoutes` (if present)

Also sets the default route from the config if provided.

### `$router->execute(): void`

Main dispatch method:
1. Processes the URL (`processURL()`)
2. Polyfills `apache_request_headers()` if it doesn't exist (for NGINX compatibility)
3. Handles `OPTIONS` requests immediately
4. Iterates routes and dispatches to the matching controller
5. Redirects to default route if no match

### `$router->getRequestUri(): string`

Returns the request URI with query string and trailing slash removed.

---

## Server Compatibility

### Apache

`apache_request_headers()` is available natively.

### NGINX / Other

The router defines a polyfill for `apache_request_headers()` that reads headers from `$_SERVER`. This is done automatically inside `execute()`.
