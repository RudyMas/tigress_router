# Tigress Router
The Router module of the Tigress Framework

## Installation
You can create a new Tigress project by using composer.
````
composer create-project tigress/tigress <project_name>
````
## Documentation

The Router is automatically loaded by the Core module.

### Usage
The Router is used to define routes for your application. You can define routes in the 'config/routes.json' file.

### Example
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
    },
    {
      "request": "POST",
      "path": "/user",
      "controller": "UserController",
      "method": "store"
    }
  ]
}
```

### Route Parameters
You can define route parameters by using curly braces `{}` in the path. The parameters will be passed to the controller method as an array of arguments.

### Example
```json
{
  "routes": [
    {
      "request": "GET",
      "path": "/user/{id}",
      "controller": "UserController",
      "method": "show"
    }
  ]
}
```

In the above example, the `id` parameter will be passed to the `show` method of the `UserController` as an argument.
In the controller you can access the parameter like this:
```php
public function show($args) {
  // $args->id will contain the value of the 'id' parameter
}
```

### Route Request Methods
You can define the request method for the route by using the `request` key in the route definition. The request method can be 'GET', 'POST', 'PUT', 'PATCH', 'DELETE' or 'OPTIONS'.

### Route Controller
You can define the controller and method to be called for the route by using the `controller` and `method` keys in the route definition.