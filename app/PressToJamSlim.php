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
    protected $pdo;
    protected $hooks;
    protected $params;
    protected $cors;
    protected $logger;
    protected $perms;
    protected $user;
   

    function __construct($custom_link = "", $cors = null) {
        $this->app = AppFactory::create();
              
        //set up all our services here
        $this->pdo = Configs\Factory::createPDO();
        $this->hooks = new Hooks($custom_link);
        $this->params = new Params();

        if (!$cors) {
            $this->cors = new Cors();
            $this->cors->origin = (isset($_SERVER['HTTP_REFERER'])) ? trim($_SERVER['HTTP_REFERER'], "/") : 0;
        } else {
            $this->cors = $cors;
        }
    }

    function __get($name) {
        if (property_exists($this, $name)) return $this->$name;
    }


    function setLogger($logger) {
        $this->logger = $logger;
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

        $this->user = new UserProfile($request);
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
            if (!$this->perms->hasModelPermission($model)) {
                throw new Exceptions\UserException(403, "This user does not have authorisation for model " . $model);
            }
        } else {
            if (!$this->perms->hasPermission($model, $state, $method)) {
                throw new Exceptions\UserException(403, "The user type " . $this->user->user . " does not have authorisation for route " . $cat . "/" . $model . "/" . $state);
            }
            $this->user->is_owner = $this->perms->requiresOwner($cat, $model, $state);
        }

        return $handler->handle($request);
    }


    function validateModel($request, $handler) {

        $this->user = new UserProfile($request);
        $class_name = Factory::createPerms($this->user);
        $this->perms = new $class_name();
        
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();

        $model = $route->getArgument("name");
       

        if (!$this->perms->hasModelPermission($model)) {
            throw new Exceptions\UserException(403, "This user does not have authorisation for model " . $model);
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
                    } else {
                        $self->blob = $contents;
                    }
                }
            }

            $self->params->apply($_GET);
            return $handler->handle($request);
        });

        $errorMiddleware = $this->app->addErrorMiddleware(true, true, true);
        $errorHandler = $errorMiddleware->getDefaultErrorHandler();
        $errorHandler->forceContentType('application/json');

        $this->app->add(function($request, $handler) use ($self) {
            $response = $handler->handle($request);
            return $response->withHeader('Access-Control-Allow-Origin', $self->cors->origin)
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
            $results = $model->$method($self->params);
            $response->getBody()->write(json_encode($results));
            return $response;
        })->add(function($request, $handler) use ($self) {
            return $self->validateRoute($request, $handler);
        });
        

        $this->app->post('/data/{route}/{name}/login', function (Request $request, Response $response, $args) use ($self) {
            $name = $args['name'];
            $model = Factory::createRepo($name, $self->user, $self->pdo, $self->params, $self->hooks);
            $model->login($self->params);
            $response = $self->user->save($response);
            $response->getBody()->write(json_encode("success"));
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

        $this->app->get("/slug/{route}/{name}", function ($request, $response, $args) use ($self) {
            $cat = $args["route"];
            $name = $args['name'];
            if ($name == $cat) {
                $response->getBody()->write(json_encode("{}"));
                return $response;
            }

            $self->params->to = $cat;

            $model = Factory::createRepo($name, $self->user, $self->pdo, $self->params, $self->hooks);
            $str = json_encode($model->getSlugTrail());
            $response->getBody()->write($str);
            return $response;
        })->add(function($request, $handler) use ($self) {
            return $self->validateModel($request, $handler);
        });


        $this->app->patch("/asset/{name}/{field}/{id}", function($request, $response, $args) use ($self) {
            $name = $args["name"];
            $field = $args["field"];
            $id = $args["id"];

            $self->params->data = ["--id"=>$id];
            $self->params->fields = [$field];

            $model = Factory::createRepo($name, $self->user, $self->pdo, $self->params, $self->hooks);
            $res = $model->primary();
            $s3writer = Configs\Factory::createS3Writer();
            $s3writer->push($res->$field->export(), $self->params->body);
            return $response;
        })->add(function($request, $handler) use ($self) {
            return $self->validateRoute($request, $handler);
        });

        $this->app->get("/asset/{name}/{field}/{id}", function($request, $response, $args) use ($self) {
            $name = $args["name"];
            $field = $args["field"];
            $id = $args["id"];

            $self->params->data = ["--id"=>$id];
            $self->params->fields = [$field];

            $model = Factory::createRepo($name, $self->user, $self->pdo, $self->params, $self->hooks);
            $res = $model->primary();
            
            $s3writer = Configs\Factory::createS3Writer();
            try {
                echo $s3writer->get($res->$field);
            } catch(\Exception $e) {
                echo $e->getMessage();
            }
            exit;
        })->add(function($request, $handler) use ($self) {
            return $self->validateModel($request, $handler);
        });

        $this->app->get("/reference/{name}/{field}", function($request, $response, $args) use ($self) {
            
            $name = $args["name"];
            $field = $args["field"];
            if (isset($self->params->data["--id"])) {
                $id= $self->params->data["--id"];
                $is_parent = false;
            } else {
                $id = $self->params->data["--parentid"];
                $is_parent = true;
            }
           
            $ref = Factory::createReference($name);
            $results = $ref->{ "get" . Factory::camelCase($field) }($id, $self->user, $self->pdo, $is_parent);
        
            $response->getBody()->write(json_encode($results));
            return $response;
        })->add(function($request, $handler) use ($self) {
            return $self->validateModel($request, $handler);
        });

        $this->app->get("/dictionary/{name}", function($request, $response, $args) use ($self) {
            $lang = new \PressToJam\Dictionary\Languages();
            if ($this->user->lang) $lang->change($this->user->lang);
            $dict = $lang->buildDictionary(Factory::camelCase($args["name"]));
            $response->getBody()->write(json_encode($dict));
            return $response;        
        })->add(function($request, $handler) use ($self) {
            return $self->validateModel($request, $handler);
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


        $this->app->group("/nav", function (RouteCollectorProxy $group) use ($self) {
            $group->get("/route-points/{route}/{name}", function($request, $response, $args) use ($self) {
                $route = $args["route"];
                $model = $args["name"];
                $profile = Factory::createNav($self->user);
                $route = $profile->getRoutePoint(Factory::camelCase($route), Factory::camelCase($model));
                $lang = new \PressToJam\Dictionary\Languages();
                if ($self->user->lang) $lang->change($self->user->lang);
                $dict = $lang->buildDictionary(Factory::camelCase($args["name"]));
                $route->applyDictionary($dict);
                $response->getBody()->write(json_encode($route));
                return $response;
            })->add(function($request, $handler) use ($self) {
                return $self->validateModel($request, $handler);
            });


            $group->get("/site-map", function($request, $response) {
                $user = new UserProfile($request);
                $profile = Factory::createNav($user);
                $response->getBody()->write(json_encode($profile->getNav()));
                return $response;
            });
        });

        $this->app->group("/core", function (RouteCollectorProxy $group) use ($self) {
            
            $group->put("/switch-tokens", function (Request $request, Response $response, $args) {
                $user = new UserProfile($request);
                $response = $user->switchTokens($request, $response);
                $response->getBody()->write(json_encode("success"));
                return $response;
            });
            
            $group->get("/check-user", function (Request $request, Response $response, $args) {
                $user = new UserProfile($request);
                $response->getBody()->write(json_encode($user));
                return $response;
            });

            $group->post("/change-role", function (Request $request, Response $response, $args) use ($self) {
                $role = $this->params->data["role"];
                $user = new UserProfile($request);
                if ($role != $user->role) {
                    $user->role = ""; //reset so we get the correct initial perms
                    $perms = Factory::createPerms($user);
                    if ($role) {
                        $roles = $perms->getRoles();
                        if (in_array($role, $roles)) {
                            $user->role = $role;
                        }
                    }
                    $response = $user->save($response);
                }
                $response->getBody()->write(json_encode($user));
                return $response;

            });
            
            $group->get("/languages", function (Request $request, Response $response, $args) use ($self) {
                $lang = new \PressToJam\Dictionary\Languages();
                $response->getBody()->write($lang->get());
                return $response;
            });

                   
            $group->post("/change-language", function (Request $request, Response $response, $args) use ($self) {
                $params = $request->getQueryParams();
                $user = new UserProfile($request);
                $user->validate();
                $user->lang = $params["__lang"];
                $response = $user->save($response);
                return $response;
            });

            
            $group->post("/logout", function (Request $request, Response $response, $args) {
                $user = new UserProfile($request);
                $response = $user->logout($response);
                $response->getBody()->write(json_encode($user));
                return $response;
            });
        });


        $this->app->map(["POST", "GET", "PUT", "DELETE"], '/hooks/{route}', function ($request, $response, $args) use ($self) {
            return $self->hooks->runRoute($args["route"], $request, $response, $self);
        });
    }


    function run() {
        $this->initMiddleware();
        $this->addRoutes();
        $this->app->run();
    }

}