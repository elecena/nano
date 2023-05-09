<?php

use \Nano\Cache;
use \Nano\Config;
use \Nano\Debug;
use \Nano\Events;
use \Nano\Logger\NanoLogger;
use \Nano\Output;
use \Nano\Response;
use \Nano\Request;
use \Nano\Router;

/**
 * Class for representing nanoPortal's application
 *
 * @property Cache $cache
 * @property Database $database
 * @property Skin $skin
 */
class NanoApp
{
    // cache object
    protected ?Cache $cache = null;

    // config
    protected Config $config;

    // debug logging
    protected Debug $debug;

    // database connection
    protected ?Database $database = null;

    // events handler
    protected Events $events;

    // response
    protected Response $response;

    // HTTP request
    protected Request $request;

    // router
    protected Router $router;

    // skin
    protected ?Skin $skin = null;

    // application's working directory
    protected string $dir = '';

    // current application instance
    protected static ?NanoApp $app = null;

    /**
     * Return the current instance of NanoApp
     *
     * @return NanoApp
     * @throws Exception
     */
    public static function app()
    {
        if (is_null(self::$app)) {
            throw new Exception('Instance of NanoApp not registered');
        }

        return self::$app;
    }

    /**
     * Override NanoApp instance, to be used by tests only!
     *
     * @param NanoApp $instance
     */
    public static function setAppInstance(self $instance)
    {
        self::$app = $instance;
    }

    /**
     * Create application based on given config
     *
     * @param string $dir
     * @param string $configSet
     * @param string $logFile
     */
    public function __construct($dir, $configSet = 'default', $logFile = 'debug')
    {
        // register the current application instance
        self::$app = $this;

        $this->dir = realpath($dir);

        // events handler
        $this->events = new Events($this);

        // read configuration
        $this->config = new Config($this->dir . '/config');
        $this->config->load($configSet);

        // debug
        $this->debug = new Debug($this->dir . '/log', $logFile);

        // log when enabled in config or when running in CLI mode
        if ($this->config->get('debug.enabled', false) || $this instanceof NanoCliApp) {
            $this->debug->enableLog();
            $this->debug->clearLogFile();

            // log nano version and when app was started
            $this->debug->log('Nano v' . Nano::VERSION . ' started at ' . date('Y-m-d H:i:s'));
            $this->debug->log('----');
        }

        // set request
        $params = $_REQUEST ?? [];
        $env = $_SERVER ?? [];

        $this->request = new Request($params, $env);
        if (!$this->request->isCLI()) {
            $this->debug->log("Request: {$this->request->getPath()} from {$this->request->getIP()}");
        }

        // response
        $this->response = new Response($this, $env);

        // requests router
        $this->router = new Router($this);

        $this->debug->log('----');
    }

    /**
     * Send an event informing that application has finished its work
     */
    public function tearDown()
    {
        $this->debug->log();

        // stats
        // TODO: move to a Monolog processor
        $responseDetails = [
            'time' => $this->getResponse()->getResponseTime() * 1000, // [ms]
            'response_code' => $this->getResponse()->getResponseCode(),
        ];

        // request type
        if ($this->request->isApi()) {
            //			$stats->increment('type.api');
            //			$stats->increment('requests.count');

            $responseDetails['type'] = 'api';
        } elseif (!$this->request->isInternal() && !$this->request->isCLI()) {
            //			$stats->increment('type.main');
            //			$stats->increment('requests.count');

            $responseDetails['type'] = 'main';
        }

        // request path
        $route = $this->getRequest()->getRoute();
        if (is_array($route)) {
            //			$stats->increment(sprintf('controller.%s', $route['controller']));
            //			$stats->increment(sprintf('method.%s.%s', $route['controller'], $route['method']));

            $responseDetails['controller'] = $route['controller'];
            $responseDetails['method'] = $route['method'];

            // log request details
            $logger = NanoLogger::getLogger('nano.request.completed');
            $logger->info('Request completed', $responseDetails);
        }

        $this->events->fire('NanoAppTearDown', [$this]);

        $this->debug->log('----');
        $this->debug->log('Script is completed');
    }

    /**
     * Checks whether given file / directory is inside application's directory (security stuff!)
     *
     * @param string $resource
     * @return bool
     */
    public function isInAppDirectory($resource)
    {
        $resource = realpath($resource);

        return strpos($resource, $this->getDirectory()) === 0;
    }

    /**
     * Returns instance of given class from /classes directory
     *
     * Class constructor is provided with application's instance and with extra parameters (if provided)
     *
     * NanoApp::factory() will ALWAYS return fresh class instance
     *
     * @deprecated create instances using new operator
     *
     * @param string $className
     * @param array $params
     * @return null|object
     */
    public function factory($className, array $params = [])
    {
        if (class_exists($className)) {
            // add NanoApp instance as the first constructor parameter
            array_unshift($params, $this);

            // http://www.php.net/manual/en/function.call-user-func-array.php#74427
            // make a reflection object
            $reflectionObj = new ReflectionClass($className);

            // use Reflection to create a new instance, using the $args (since PHP 5.1.3)
            $instance = $reflectionObj->newInstanceArgs($params);
        } else {
            $instance = null;
        }

        return $instance;
    }

    /**
     * Return fresh instance of a given controller
     *
     * @param string $controllerName
     * @return Controller
     */
    public function getController($controllerName)
    {
        return Controller::factory($this, $controllerName);
    }

    /**
     * Internally route the request and return raw data (array) or an Output object wrapping the response
     *
     * @param Request $request
     * @return bool|mixed|Output
     */
    protected function route(Request $request)
    {
        // route given request
        $resp = $this->router->route($request);

        // $resp can be either raw data (array) or Output object wrapping the response from the module
        return $resp;
    }

    /**
     * Dispatch given request
     *
     * Returns raw data returned by the module
     *
     * @param Request $request
     * @return bool|mixed|Output
     */
    public function dispatchRequest(Request $request)
    {
        // route given request
        $resp = $this->route($request);

        // $resp can be either raw data (array) or Output object wrapping the response from the module
        if ($resp instanceof Output) {
            $resp = $resp->getData();
        }

        return $resp;
    }

    /**
     * Dispatch request given by the controller and method name (and optional parameters)
     *
     * Returns raw data returned by the module
     *
     * @param string $controllerName
     * @param string $methodName
     * @param array $params
     * @return bool|mixed|Output
     */
    public function dispatch($controllerName, $methodName = '', array $params = [])
    {
        $request = Request::newFromControllerName($controllerName, $methodName, $params, Request::INTERNAL);

        // route given request
        return $this->dispatchRequest($request);
    }

    /**
     * Render the results of given request
     *
     * @param Request $request
     * @return string template's output for data returned by the module
     */
    public function renderRequest(Request $request)
    {
        $resp = $this->route($request);

        if ($resp instanceof Output) {
            // module returned wrapped data
            $resp = $resp->render();
        }

        return $resp;
    }

    /**
     * Render the results of request given by the controller and method name (and optional parameters)
     *
     * @param string $controllerName
     * @param string $methodName
     * @param array $params
     * @return string template's output for data returned by the module
     */
    public function render($controllerName, $methodName = '', array $params = [])
    {
        $request = Request::newFromControllerName($controllerName, $methodName, $params, Request::INTERNAL);

        // render given request
        return $this->renderRequest($request);
    }

    /**
     * Call given function and handle any exception thrown
     *
     * Will render HTTP 500 page with error details
     *
     * @param callable $fn function to call
     * @param callable|null $handler custom exception handling function to call
     * @return mixed|Throwable value return by function called or exception that was thrown
     */
    public function handleException(callable $fn, ?callable $handler = null)
    {
        try {
            return $fn();
        } catch (\Throwable $e) {
            $response = $this->getResponse();
            $response->setResponseCode(Response::INTERNAL_SERVER_ERROR);
            $response->setContentType('text/plain');
            $response->setHeader('X-Error', get_class($e));
            $response->setCacheDuration(0);

            // log the exception
            $logger = NanoLogger::getLogger('nano.app.exception');
            $logger->error($e->getMessage(), [
                'exception' => $e,
            ]);

            if (is_callable($handler)) {
                $handler($e);
            } else {
                $error = sprintf('%s: %s', get_class($e), $e->getMessage());

                if (ini_get('display_errors')) {
                    $error .= "\n\n" . $e->getTraceAsString();
                }

                $response->setContent($error);
            }
            return $e;
        }
    }

    /**
     * Return path to application
     */
    public function getDirectory(): string
    {
        return $this->dir;
    }

    /**
     * Lazy-load the cache instance
     * @throws Exception
     */
    public function getCache(): Cache
    {
        if (is_null($this->cache)) {
            // setup cache (using default driver if none provided)
            $cacheSettings = $this->config->get('cache', [
                'driver' => 'file',
            ]);

            $this->cache = Cache::factory($cacheSettings);
        }

        return $this->cache;
    }

    /**
     * Return config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * Lazy-load the database instance
     * @throws Exception
     */
    public function getDatabase(): Database
    {
        // lazy connection handling
        if (is_null($this->database)) {
            // set connection to database (using db.default config entry)
            $this->database = Database::connect($this);

            if (is_null($this->database)) {
                throw new Exception(__METHOD__ . ': unable to create a database instance!');
            }
        }

        return $this->database;
    }

    /**
     * Return debug
     */
    public function getDebug(): Debug
    {
        return $this->debug;
    }

    /**
     * Return events
     */
    public function getEvents(): Events
    {
        return $this->events;
    }

    /**
     * Return response
     */
    public function getResponse(): Response
    {
        return $this->response;
    }

    /**
     * Return request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * Set a request for this NanoApp, used by tests
     * @param Request $request
     */
    public function setRequest(Request $request): void
    {
        $this->request = $request;
    }

    /**
     * Return router
     */
    public function getRouter(): Router
    {
        return $this->router;
    }

    /**
     * Return skin
     *
     * @return Skin
     * @throws Exception
     */
    public function getSkin(): Skin
    {
        // lazy load the skin
        if (is_null($this->skin)) {
            // use the default skin
            $skinName = (string) $this->config->get('skin', 'default');

            // allow to override the default choice
            $this->events->fire('NanoAppGetSkin', [$this, &$skinName]);

            // create an instance of the skin
            $this->skin = Skin::factory($this, $skinName);
        }

        return $this->skin;
    }
}
