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



    function validateRoute($request, $handler) {

        $class_name = Factory::createProfile($this->user->user);
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
            //write our error messages and reset to work with error handling
            try {
                $response = $handler->handle($request);
            } catch(\Exception $e) {
                $excep = new HttpException($request, $e->code, $e->message, $e);
                $excep->setTitle($e->title);
                $excep->setDescription($e->description);
                throw $excep;
            } 
        });

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

            $data_row = new DataRow($name);
            $data_row->{ $state }();
            $data_row->map($self->params);

            if ($state == "login") $method = "get";
            $res = StorageHandler::exec($self->pdo, $data_row, $state);

            if ($state == "post") {
                $response->getBody()->write(json_encode(["__id"=>$self->pdo->lastInsertId()]));
            } else if ($state == "get" OR $state == "parent") {
                $results_handler = new ResultsHandler($data_row);
                $response->getBody()->write(json_encode($results_handler->getResults($res)));
            } else if ($state == "primary" OR $state == "count") {
                $results_handler = new ResultsHandler($data_row);
                $response->getBody()->write(json_encode($results_handler->getResult($res)));
            } else if ($state == "login") {
                $results_handler = new ResultsHandler($data_row);
                $data = $results_handler->getResult($res);
                $self->user->id = $data->__id;
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

            $data_row = new DataRow($name);
            $data_row->{ $state }();
        
            $response->getBody()->write(json_encode($data_row->getSchema()));
            return $response;
        })->add(function($request, $response) use ($self) {
            $self->validateRoute($request, $response);
        });

        $this->app->patch("/asset/{name}/{field}/{id}", function($request, $response, $args) use ($self) {
            $name = $args["name"];
            $field = $args["field"];
            $id = $args["id"];

            $data_row = new DataRow($name);
            $data_row->{"asset" . Factory::camelCase($field)}();
            $res = StorageHandler::exec($self->pdo, $data_row, "get");
            $results_handler = new ResultsHandler($data_row);
            $response = $results_handler->getResult($res);
            $s3writer = Configs\Factory::createS3Writer();
            $s3writer->push($response->nextField());
        });

        $this->app->get("/reference/{name}/{field}/{id}", function($request, $response, $args) use ($self) {
            $name = $args["name"];
            $field = $args["field"];
            $id = $args["id"];


            $data_row = new DataRow($meta_row);
            $params = new Params();
            if ($data_row->{"reference" . Factory::camelCase($field) . "Common"}($data_row)) {
                $common = StorageHandler($self->pdo, $data_row, "get");
                $handler = new ResultsHandler($data_row);
                $row = $handler->get();
                $params->data = $row->nextField();
            }

            //run ref data row through exec process

            $data_row=$meta_row->get{ "reference" . Factory::camelCase($name) , "Datarow"}($params); 
            $res = StorageHandler($self->pdo, $data_row, "get");
            $response = new ResponseProcess();
            $response->getBody()->write(json_encode($response->getAll($res)));
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

            $group->get("/dictionary", function (Request $request, Response $response, $args) use ($self) {
                $lang = new \PressToJam\Dictionary\Languages();
                $dictionary = $self->user->getDictionary();
                $response->getBody()->write(json_encode($dictionary));
                return $response;
            });

            
            $group->post("/change-language", function (Request $request, Response $response, $args) use ($self) {
                $params = $request->getQueryParams();
                $self->user->lang = $params["__lang"];
                $self->user->save($response);
                return $response;
            });

            $app->get("/site-map", function($request, $response) use ($self) {
                $profile = Factory::createProfile();
                $func = "get";
                if ($self->user->row) $func .= Factory::camelCase($self->user->user);
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