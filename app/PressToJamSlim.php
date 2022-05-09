<?php
namespace PressToJamCore;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use Slim\Routing\RouteContext;


class PressToJamSlim {


    protected $app;
    protected $user;
    protected $pdo;
    protected $hooks;
    protected $params;
   

    function __construct() {
        $this->app = AppFactory::create();
        $this->cors_headers = [
            "Content-Type",
            "Authorization",
            "Referer",
            "sec-ch-ua",
            "sec-ch-ua-mobile",
            "User-Agent"];

            
        //set up all our services here
        $this->pdo = Configs\Factory::createPDO();
        $this->hooks = new Hooks(__DIR__ . "/custom/custom.php");
        $this->user = new UserProfile($this->app->getCallableResolver());
        $this->params = new Params();


    }


    function regAutoload($namespace, $base)
    {

        //register psr-4 autoload
        spl_autoload_register(function ($class_name) use ($namespace, $base) {
            $parts = explode("\\", $class_name);
            $file = $base .  "/";
            $onamespace = array_shift($parts);
            if ($onamespace == $namespace) {
                $file .= implode("/", $parts) . ".php";
                if (file_exists($file)) {
                    require_once($file);
                    return;
                } else {
                    echo "Can't find file " . $file;
                }
            }
        });
    }

    function camelCase($str) {
        return str_replace('-', '', ucwords($tr, "-"));
    }


    function validateRoute($request, $handler) {

        $class_name = "PressToJam\Profiles\\" . $this->camelCase($this->user->user);
        $profile = new $class_name();
        
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();

        $model = $route->getArgument("name");
        $method = strtolower($request->getMethod());
        if ($route->hasArgument("state")) {
            $state = $route->hasArgument("state");
        } else {
            $state = $method;
        }

        if (!$profile->hasPermission($model, $method, $state)) {
            throw new Error();
        }
        
        return $handler->handle($request);
    }


    function initMiddleware() {

        $self = $this;

        $this->app->add(function($request, $handler) {
            $response = $handler->handle($request);
            return $response->withHeader(
                'Content-Type',
                'application/json'
            );
        });

        $this->app->addRoutingMiddleware();

        $this->app->add(function($request, $handler) use ($self) {
            $contentType = $request->getHeaderLine('Content-Type');
            $method = $request->getMethod();
            if ($method != "GET") {
                if($method == "POST" AND strstr($contentType, "application/x-www-form-urlencoded")) $self->params->apply($_POST);
                else if (strstr($contentType, 'application/json')) {
                    $contents = json_decode(file_get_contents('php://input'), true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $self->params->apply($request->withParsedBody($contents));
                    }
                }
            }

            $self->params->apply($_GET);
            return $handler->handle($request);
        });


        $app->addRoutingMiddleware();
        $errorMiddleware = $app->addErrorMiddleware(true, true, true);
        $errorHandler = $errorMiddleware->getDefaultErrorHandler();
        $errorHandler->forceContentType('application/json');
    }


    function initCors($cors_headers = [], $cors_domains = []) {
        if (!isset($_SERVER['HTTP_ORIGIN'])) return;

        $this->app->options('/{routes:.+}', function ($request, $response, $args) {
            return $response;
        });

        if (!$cors_headers) $cors_headers = $this->cors_headers;

        $origin = $_SERVER['HTTP_ORIGIN'];
        if ($cors_domains AND !in_array($origin, $cors_domains)) {
            $origin = 0;
        }

        $this->app->add(function ($request, $handler) use ($origin, $cors_headers) {
            $response = $handler->handle($request);
            return $response
                ->withHeader('Access-Control-Allow-Origin', $origin)
                ->withHeader('Access-Control-Allow-Headers', implode(",", $cors_headers))
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
        });
    }

    function initErrorHandlers() {

    }



    function addRoutes() {
        $self = $this;
        
        $this->app->map(['GET','POST','PUT','DELETE'], '/data/{name}[/{state}]', function (Request $request, Response $response, $args) use ($self) {
            $name = $args['name'];
            $method = strtolower($request->getMethod());
            $state = (isset($args["state"])) ? $args["state"] : $method;

            $model = new Model($name, $self->pdo, $self->user, $self->params);
            $model->initMeta($state);

            if ($state == "login") $method = "get";
            $res = $model->exec($method);

            if ($state == "post") {
                $response->getBody()->write(json_encode(["__id"=>$self->pdo->lastInsertId()]));
            } else if ($state == "get" OR $state == "parent") {
                $response->getBody()->write(json_encode($model->getResults($res)));
            } else if ($state == "primary" OR $state == "count") {
                $response->getBody()->write(json_encode($model->getResult($res)));
            } else if ($state == "login") {
                $data = $model->getResult($res);
                $self->user->id = $data["__id"];
                $self->user->user = $name;
                $self->user->save($response);
            }
            return $response;
        })->add(function($request, $response) use ($self) {
            $self->validateRoute($request, $response);
        });

        $this->app->map(['GET','POST','PUT','DELETE'], "/route/{name}[/{state}]", function ($request, $response) use ($self) {
            $name = $args['name'];
            $method = strtolower($request->getMethod());
            $state = (isset($args["state"])) ? $args["state"] : $method;

            $model = new Model($name, $self->pdo, $self->user, $self->params);
            $model->initMeta($state);

            $dictionary = $self->user->getDictionary();
            $schema = $model->getSchema();
            $response->getBody()->write(json_encode($dictionary->apply($schema)));
            return $response;
        })->add(function($request, $response) use ($self) {
            $self->validateRoute($request, $response);
        });

        $this->app->patch("/asset/{name}/{field}/{id}", function($request, $response, $args) use ($self) {
            $name = $args["name"];
            $field = $args["field"];
            $id = $args["id"];

            $model = new Model($name, $self->pdo, $self->user, $self->params);
            $model->initMeta("asset" . $this->camelCase($field));

            $res = $model->exec("get");
            $response = $model->getResult($res);
            $s3writer = Configs\Factory::createS3Writer();
            $s3writer->push($response["{{ field }}"]);
        });

        $this->app->get("/reference/{name}/{field}/{id}", function($request, $response, $args) use ($self) {
            $name = $args["name"];
            $field = $args["field"];
            $id = $args["id"];
            
            $model = new Model($name, $self->pdo, $self->user, $self->params);
            $model->initMeta("ref" . $this->camelCase($field) . "Common");

            $res = $model->exec("get");
            $response = $model->getResult($res);

            //$model = new mode($name, )
        });

        $this->app->map(["POST", "PUT"], "/import/{name}", function($request, $response, $args) use ($self) {

        });

        $this->app->map(["POST", "DELETE"], "/bulk/{name}", function($request, $response, $args) use ($self) {

        });

        if (isset($_ENV['DEV'])) {
            $this->app->get("/debugsql/{name}/{state}", function($request, $response, $args) use ($self) {

            });
        
            $this->app->get("/debuguser", function($request, $response, $args) use ($self) {

            });
        
        }

        $this->app->group("/core", function (RouteCollectorProxy $group) use ($self) {
            
            $group->get("/switch-tokens", function (Request $request, Response $response, $args) use ($self) {
                $self->user->switchTokens($request, $response);
                return $response;
            });
            
            $group->get("/check-user", function (Request $request, Response $response, $args) use ($self) {
                $response->getBody()->write(json_encode($self->user));
                return $response;
            });
            
            $group->get("/languages", function (Request $request, Response $response, $args) use ($self) {
                $lang = new \PressToJam\Dictionary\Languages();
                $response->getBody()->write($lang->get());
                return $response;
            });

            
            $group->post("/change-language", function (Request $request, Response $response, $args) use ($self) {
                $params = $request->getQueryParams();
                $self->user->lang = $params["__lang"];
                $self->user->save($response);
                return $response;
            });

            $app->get("/site-map", function($request, $response) use ($self) {
                $class_name = "PressToJam\Profiles\\" . $this->camelCase($self->user->user);
                $profile = new $class_name();
                $func = "get";
                if ($self->user->row) $func .= $this->camelCase($self->user->user);
                $func .= "Nav";
                $map = $profile->$func();
                $response->getBody()->write($map);
            });
            
            $group->post("/logout", function (Request $request, Response $response, $args) use ($self) {
                $self->user->logout($response);
                return $response;
            });
        });
    }


    function run() {
        $this->app->run();
    }

}