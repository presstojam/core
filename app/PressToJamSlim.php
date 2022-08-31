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
    protected $profile;
    protected $user;
   

    function __construct($custom_link = "", $cors = null) {
        $this->app = AppFactory::create();
              
        //set up all our services here
        $this->pdo = WrapperFactory::createPDO();
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


    function __set($key, $val) {
        if (property_exists($this, $key)) $this->$key = $val;
    }
    
    function setLogger($logger) {
        $this->logger = $logger;
    }


    function validateModel($request, $handler) {

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();

        $model = $route->getArgument("model");
        $method = strtolower($request->getMethod());
         

        if (!$this->profile->hasModelPermissions($model, $method)) {
            throw new Exceptions\UserException(403, "User " . $this->user->user . " does not have " . $method . " authorisation for model " . $model);
        }
      
        return $handler->handle($request);
    }


    function validateProfile($request, $handler) {

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();

        $model = "user-login";
        $method = strtolower($request->getMethod());
       

        if (!$this->profile->hasModelPermissions($model, $method)) {
            throw new Exceptions\UserException(403, "User " . $this->user->user . " does not have " . $method . " authorisation for model " . $model);
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
            } catch(Exceptions\ValidationException $e) {
                throw $e;
            } catch(\Exception $e) {
                $code = $e->getCode();
                if ($code > 500) $code = 500;
                $excep = new HttpException($request, $e->getMessage(), $code, $e);
                if (method_exists($e, "getTitle")) {
                    $excep->setTitle($e->getTitle());
                    $excep->setDescription($e->getDescription());
                }
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
                    if ($contentType == "application/json") {
                        $contents = json_decode($str, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $self->params->apply($contents);
                        } else {
                            throw new \Exception("Unable to process json request body");
                        }
                    }
                }
            }

            $self->params->apply($_GET);
            return $handler->handle($request);
        });


        $this->app->add(function($request, $handler) use ($self) {
            try {
                if (!$self->user) {
                    $force_auth = $request->getHeaderLine('X-FORCE-AUTH-COOKIES');
                    if ($force_auth) {
                        $self->user = new UserProfile();
                    } else {
                        $self->user = new UserProfile($request);
                    }
                }
                 
                $self->profile = Factory::createProfile($self->user);
            } catch(\Exception $e) {
                $code = $e->getCode();
                if ($code > 500) $code = 500;
                $excep = new HttpException($request, $e->getMessage(), $code, $e);
                if (method_exists($e, "getTitle")) {
                    $excep->setTitle($e->getTitle());
                    $excep->setDescription($e->getDescription());
                }
                throw $excep; 
            }
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

        $this->app->map(['POST', 'PUT', 'DELETE'], '/data/{model}', function (Request $request, Response $response, $args) use ($self) {
            $name = $args['model'];
            $method = strtolower($request->getMethod());
        
            $model = Factory::createModel($name, $self->user, $self->pdo, $self->params, $self->hooks);
            $results = $model->$method();
            $response->getBody()->write(json_encode($results));
            return $response;
        })->add(function($request, $handler) use ($self) {
            return $self->validateModel($request, $handler);
        });

        $this->app->put('/data/{model}/resort', function (Request $request, Response $response, $args) use ($self) {
            $name = $args['model'];
            $method = strtolower($request->getMethod());
        
            $model = Factory::createModel($name, $self->user, $self->pdo, $self->params, $self->hooks);
            $results = $model->resort();
            $response->getBody()->write(json_encode($results));
            return $response;
        })->add(function($request, $handler) use ($self) {
            return $self->validateModel($request, $handler);
        });
        

        $this->app->post('/login/{name}', function (Request $request, Response $response, $args) use ($self) {
            $name = $args['name'];
            $profile = new Profile();
            $profile->login($self->user, $self->pdo, $self->params, $name);
            $response = $self->user->save($response);
            $response->getBody()->write(json_encode("success"));
            return $response;
        })->add(function($request, $handler) use ($self) {
            return $self->validateProfile($request, $handler);
        });


        $this->app->post('/login/anon/{name}', function (Request $request, Response $response, $args) use ($self) {
            $name = $args['name'];
            $self->params->apply(["type"=>$name]);
            $self->user->user = $name;
            $profile = Factory::createProfile($self->user);
            if (!$profile->anonymous) {
                throw new HttpException($request, "This profile doesn't allow anonymous connections", 500);
            }
            $profile->anonymousCreate($self->user, $self->pdo, $self->params);
            $response = $self->user->save($response);
            $response->getBody()->write(json_encode("success"));
            return $response;
        })->add(function($request, $handler) use ($self) {
            return $self->validateProfile($request, $handler);
        });


        $this->app->get('/data/{model}[/{state}]', function (Request $request, Response $response, $args) use ($self) {
            $name = $args['model'];
            $state = (isset($args["state"])) ? $args["state"] : "get";
           
            $model = Factory::createRepo($name, $self->user, $self->pdo, $self->params, $self->hooks);
            $results = $model->$state($self->params);
            $response->getBody()->write(json_encode($results));
            return $response;
        })->add(function($request, $handler) use ($self) {
            return $self->validateModel($request, $handler);
        });

        $this->app->get('/count/{model}', function (Request $request, Response $response, $args) use ($self) {
            $name = $args['model']; 
            $model = Factory::createRepo($name, $self->user, $self->pdo, $self->params, $self->hooks);
            $results = $model->getCount($self->params);
            $response->getBody()->write(json_encode($results));
            return $response;
        })->add(function($request, $handler) use ($self) {
            return $self->validateModel($request, $handler);
        });


        $this->app->map(['GET','POST','PUT'], "/meta/{model}[/{state}]", function ($request, $response, $args) use ($self) {
            $model = $args["model"];
            $state = (isset($args["state"])) ? $args["state"] : strtolower($request->getMethod());

            $route = Factory::createRoute($model, $self->user, $self->params);
            $arr = $route->$state();
            $response->getBody()->write(json_encode($arr));
            return $response;
        })->add(function($request, $handler) use ($self) {
            return $self->validateModel($request, $handler);
        });

 

        $this->app->patch("/asset/{model}/{field}/{id}", function($request, $response, $args) use ($self) {
            $name = $args["model"];
            $field = $args["field"];
            $id = $args["id"];

            $self->params->data = ["--id"=>$id];
            $self->params->fields = [$field];

            $model = Factory::createRepo($name, $self->user, $self->pdo, $self->params, $self->hooks);
            $res = $model->primary();
            $s3writer = WrapperFactory::createS3();

            $body = file_get_contents('php://input');
            try {
                $s3writer->push($res->$field, $body);
            } catch(\Exception $e) {
                echo $e->getMessage();
            }
           $response->getBody()->write(json_encode("success"));
           return $response;
        })->add(function($request, $handler) use ($self) {
            return $self->validateModel($request, $handler);
        });

        $this->app->get("/asset/{model}/{field}/{id}", function($request, $response, $args) use ($self) {
            $name = $args["model"];
            $field = $args["field"];
            $id = $args["id"];

            $self->params->data = ["--id"=>$id];
            $self->params->fields = [$field];

            $model = Factory::createRepo($name, $self->user, $self->pdo, $self->params, $self->hooks);
            $res = $model->primary();
            
            $s3writer = WrapperFactory::createS3();
            try {
                echo $s3writer->get($res->$field);
            } catch(\Exception $e) {
                echo $e->getMessage();
            }
            exit;
        })->add(function($request, $handler) use ($self) {
            return $self->validateModel($request, $handler);
        });

        $this->app->get("/reference/{model}/{field}", function($request, $response, $args) use ($self) {
            
            $name = $args["model"];
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

        $this->app->get("/dictionary", function($request, $response, $args) use ($self) {
            $dict = file_get_contents(env("_ROOT_") . "/dictionary/" . $self->user->user . ".json");
            $response->getBody()->write($dict);
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

        $this->app->get("/site-map", function($request, $response) use ($self) {
            $response->getBody()->write(json_encode($self->profile->getSitemap()));
            return $response;
        });

        $this->app->group("/core", function (RouteCollectorProxy $group) use ($self) {
            
            $group->put("/switch-tokens", function (Request $request, Response $response, $args) use ($self) {
                $response = $self->user->switchTokens($request, $response);
                $response->getBody()->write(json_encode("success"));
                return $response;
            });
            
            $group->get("/check-user", function (Request $request, Response $response, $args) use ($self) {
                $response->getBody()->write(json_encode($self->user));
                return $response;
            });

            $group->post("/change-role[/{role}]", function (Request $request, Response $response, $args) use ($self) {
                $role = (isset($args['role'])) ? $args["role"] : "";
                if (!$role) {
                    $self->user->role = "";
                    $response = $self->user->save($response);
                } else if ($role != $user->role) {
                    $self->user->role = ""; //reset so we get the correct initial perms
                    $perms = Factory::createPerms($self->user);
                    if ($role) {
                        $roles = $perms->getRoles();
                        if (in_array($role, $roles)) {
                            $user->role = $role;
                        }
                    }
                    $response = $self->user->save($response);
                }
                $response->getBody()->write(json_encode($self->user));
                return $response;
            });
            
            $group->get("/languages", function (Request $request, Response $response, $args) use ($self) {
                $lang = new \PressToJam\Dictionary\Languages();
                $response->getBody()->write(json_encode($lang->get()));
                return $response;
            });

                   
            $group->post("/change-language", function (Request $request, Response $response, $args) use ($self) {
                $params = $request->getQueryParams();
                $self->user->validate();
                $self->user->lang = $params["__lang"];
                $response = $self->user->save($response);
                return $response;
            });

            
            $group->post("/logout", function (Request $request, Response $response, $args) use ($self) {
                $response = $self->user->logout($response);
                $response->getBody()->write(json_encode($self->user));
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