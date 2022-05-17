<?php
namespace PressToJamCore;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Slim\Factory\AppFactory;
use \Slim\Routing\RouteCollectorProxy;
use \Slim\Routing\RouteContext;
use \Slim\Exception\HttpException;


class PressToJamSlim {


    protected $app;
    protected $user;
    protected $pdo;
    protected $hooks;
    protected $params;
    protected $cors;
    protected $perms;
   

    function __construct($cors = null) {
        $this->app = AppFactory::create();
              
        //set up all our services here
        $this->pdo = Configs\Factory::createPDO();
        $this->hooks = new Hooks(__DIR__ . "/custom/custom.php");
        $this->params = new Params();

        if (!$cors) {
            $this->cors = new Cors();
            $this->cors->origin = (isset($_SERVER['HTTP_ORIGIN'])) ? $_SERVER['HTTP_ORIGIN'] : 0;
        } else {
            $this->cors = $cors;
        }
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

        $class_name = Factory::createPerms($this->user);
        $this->perms = new $class_name();
        
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();

        $cat = $route->getArgument("route");
        $model = $route->getArgument("name");
        $method = strtolower($request->getMethod());
        $args = $route->getArguments();
        $state = (isset($args["state"])) ? $args["state"] : $method;

        if ($state == "model") {
            if (!$this->perms->hasModelPermission($cat, $model)) {
                throw new Exceptions\UserException(403, "This user does not have authorisation for model " . $model);
            }
        } else if (!$this->perms->hasPermission($cat, $model, $state, $method)) {
            throw new Exceptions\UserException(403, "This user does not have authorisation for this route");
        }
        
        return $handler->handle($request);
    }


    function initMiddleware() {

        $self = $this;

        $this->app->addRoutingMiddleware();

        $this->app->add(function($request, $handler) {
            //write our error messages and reset to work with error handling
            try {
                $response = $handler->handle($request);
            } catch(\Exception $e) {
                $excep = new HttpException($request, $e->getMessage(), $e->getCode(), $e);
                $excep->setTitle($e->getTitle());
                $excep->setDescription($e->getDescription());
                throw $excep;
            } 
            return $response;
        });
      

        $this->app->add(function($request, $handler) use ($self) {
            $self->user = new UserProfile($request);
            return $handler->handle($request);
        });



        $errorMiddleware = $this->app->addErrorMiddleware(true, true, true);
        $errorHandler = $errorMiddleware->getDefaultErrorHandler();
        $errorHandler->forceContentType('application/json');


        $this->app->add(function ($request, $handler) use ($self) {
            $contentType = $request->getHeaderLine('Content-Type');
            $method = $request->getMethod();
            if ($method != "GET") {
                if ($method == "POST") {
                    $self->params->apply($_POST);
                }
                
                $str = file_get_contents('php://input');
                if ($str) {
                    $contents = json_decode($str, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $self->params->apply($contents);
                    }
                }
            }

            $self->params->apply($_GET);

            $response = $handler->handle($request);
            return $response
                ->withHeader('Access-Control-Allow-Origin', $self->cors->origin)
                ->withHeader('Access-Control-Allow-Headers', implode(",", $self->cors->headers))
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
                ->withHeader('Access-Control-Allow-Credentials', 'true')
                ->withHeader('Content-Type', 'application/json');
        });
        
    }


    function addRoutes() {
        $self = $this;

        $this->app->options('/{routes:.+}', function ($request, $response, $args) {
            return $response;
        });

        $this->app->map(['POST', 'PUT', 'DELETE'], '/data/{route}/{name}', function (Request $request, Response $response, $args) use ($self) {
            $name = $args['name'];
            $method = strtolower($request->getMethod());
        
            $model = Factory::createModel($name, $self->user, $self->pdo, $self->params, $self->hooks);
            $results = $model->$state($self->params);
            $response->getBody()->write(json_encode($results));
            return $response;
        })->add(function($request, $handler) use ($self) {
            return $self->validateRoute($request, $handler);
        });
        

        $this->app->post('/data/{name}/login', function (Request $request, Response $response, $args) use ($self) {
            $name = $args['name'];
            $model = Factory::createRepo($name, $self->user, $self->pdo, $self->hooks);
            $results = $model->login($self->params);
            $self->user->id = $data->__id;
            $self->user->user = $name;
            $self->user->save($response);
            return $response;
        })->add(function($request, $handler) use ($self) {
            return $self->validateRoute($request, $handler);
        });

        $this->app->get('/data/{route}/{name}[/{state}]', function (Request $request, Response $response, $args) use ($self) {
            $name = $args['name'];
            $state = (isset($args["state"])) ? $args["state"] : "get";
           
            $model = Factory::createRepo($name, $self->user, $self->pdo, $self->params, $self->hooks);
            $results = $model->$state($self->params);
            $response->getBody()->write(json_encode($results));
            return $response;
        })->add(function($request, $handler) use ($self) {
            return $self->validateRoute($request, $handler);
        });

        $this->app->post("/route/{name}/login", function ($request, $response, $args) use ($self) {
            $name = $args['name'];
            $route = Factory::createRoute($name, $self->user, $self->params);
            $details = $route->login($self->params);
            $str = json_encode($details);
            $response->getBody()->write($str);
            return $response;
        })->add(function($request, $handler) use ($self) {
            return $self->validateRoute($request, $handler);
        });

        $this->app->map(['GET','POST','PUT','DELETE'], "/route/{route}/{name}[/{state}]", function ($request, $response, $args) use ($self) {
            $cat = $args["route"];
            $name = $args['name'];
            $method = strtolower($request->getMethod());
            $state = (isset($args["state"])) ? $args["state"] : $method;

            $route = Factory::createRoute($name, $self->user, $self->params);
            $details = ($state != "model") ? $route->$state($self->params) : $route->model($self->perms, $cat);
            $str = json_encode($details);
            $response->getBody()->write($str);
            return $response;
        })->add(function($request, $handler) use ($self) {
            return $self->validateRoute($request, $handler);
        });


        $this->app->patch("/asset/{route}/{name}/{field}[/{id}]", function($request, $response, $args) use ($self) {
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
            return $response;
        });

        $this->app->get("/reference/{route}/{name}/{field}/{id}", function($request, $response, $args) use ($self) {
            
            $name = $args["name"];
            $field = $args["field"];
            $id = $args["id"];

            $ref = Factory::createReference($name);
            $results = $ref->{ "get" . Factory::camelCase($field) }($id, $self->user, $self->pdo);
        
            $response->getBody()->write(json_encode($results));
            return $response;
        });

        $this->app->map(["POST", "PUT"], "/import/{name}", function($request, $response, $args) use ($self) {
            return $response;
        });

        $this->app->map(["POST", "DELETE"], "/bulk/{name}", function($request, $response, $args) use ($self) {
            return $response;
        });

        if (isset($_ENV['DEV'])) {
            $this->app->get("/debugsql/{name}/{state}", function($request, $response, $args) use ($self) {
                return $response;
            });
        
            $this->app->get("/debuguser", function($request, $response, $args) use ($self) {
                return $response;
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

            $group->post("/change-role", function (Request $request, Response $response, $args) use ($self) {
                $role = $this->params->data["role"];
                if ($role != $self->user->role) {
                    $self->user->role = ""; //reset so we get the correct initial perms
                    $perms = Factory::createPerms($self->user);
                    if ($role) {
                        $roles = $perms->getRoles();
                        if (in_array($role, $roles)) {
                            $self->user->role = $role;
                        }
                    }
                    $self->user->save($response);
                }
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

            $group->get("/site-map", function($request, $response) use ($self) {
                $profile = Factory::createNav($self->user);
                $response->getBody()->write(json_encode($profile->getNav()));
                return $response;
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