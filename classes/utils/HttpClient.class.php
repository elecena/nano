<?php

use Nano\Logger\NanoLogger;
use Nano\Http\Response;
use Nano\Http\ResponseException;

/**
 * Wrapper for cURL based HTTP client
 *
 * @see http://php.net/manual/en/ref.curl.php
 */

class HttpClient
{

    // HTTP request types
    const GET = 'GET';
    const POST = 'POST';
    const HEAD = 'HEAD';

    // User-Agent
    private $userAgent;

    // cURL resource
    private $handle;

    // cURL version
    private $version;

    // response headers
    private $respHeaders = [];

    // request headers
    private $reqHeaders = [];

    // timeout
    private $timeout = 15;

    private $logger;

    // request cookies set manually
    private $cookies = [];

    /**
     * Setup HTTP client
     */
    public function __construct()
    {
        // cURL info
        $info = curl_version();
        $this->version = $info['version'];

        $phpVersion = phpversion();

        // set up cURL library
        $this->handle = curl_init();

        $this->logger = self::getLogger();

        // set user agent
        $this->setUserAgent('NanoPortal/' . Nano::VERSION . " libcurl/{$this->version}" . " php/{$phpVersion}");

        curl_setopt_array($this->handle, [
            CURLOPT_ENCODING => 'gzip',
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HEADER => false,
            /**
             * @see http://it.toolbox.com/wiki/index.php/Use_curl_from_PHP_-_processing_response_headers
             */
            CURLOPT_HEADERFUNCTION => function ($ch, $raw) {
                // parse response's line
                $parts = explode(': ', trim($raw), 2);

                if (count($parts) == 2) {
                    $this->respHeaders[$parts[0]] = $parts[1];
                }

                return strlen($raw);
            },
            CURLOPT_MAXREDIRS => 2,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => $this->timeout,
        ]);
    }

    /**
     * Get logger instance
     */
    protected static function getLogger(): \Monolog\Logger
    {
        static $logger;

        if (!$logger) {
            $logger = NanoLogger::getLogger('nano.http');
        }

        return $logger;
    }

    /**
     * Close a session,free all resources and store cookies in jar file
     */
    public function close()
    {
        $this->logger->debug(__METHOD__);
        curl_close($this->handle);
    }

    /**
     * Set proxy to be used for HTTP requests
     **
     * @param string $proxy
     * @param int $type
     */
    public function setProxy(string $proxy, int $type = CURLPROXY_HTTP)
    {
        curl_setopt($this->handle, CURLOPT_PROXY, $proxy);
        curl_setopt($this->handle, CURLOPT_PROXYTYPE, $type);

        $this->logger->debug(__METHOD__, ['proxy' => $proxy, 'type' => $type]);
    }

    /**
     * Set user agent identification used by HTTP client
     */
    public function setUserAgent(string $userAgent)
    {
        $this->userAgent = $userAgent;

        curl_setopt($this->handle, CURLOPT_USERAGENT, $this->userAgent);

        $this->logger->debug(__METHOD__, ['agent' => $this->userAgent]);
    }

    /**
     * Get user agent identification used by HTTP client
     */
    public function getUserAgent()
    {
        return $this->userAgent;
    }

    /**
     * Set request headers
     */
    public function setRequestHeader(string $header, string $value)
    {
        $this->reqHeaders[] = "$header: $value";

        $this->logger->debug(__METHOD__, ['header' => $header, 'value' =>$value]);
    }

    /**
     * Set timeout for a single request
     */
    public function setTimeout(int $timeout)
    {
        $this->timeout = $timeout;

        curl_setopt($this->handle, CURLOPT_TIMEOUT, $this->timeout);
    }

    /**
     * Use given cookie jar file
     */
    public function useCookieJar(string $jarFile)
    {
        $this->logger->debug(__METHOD__, ['jar' => $jarFile]);

        curl_setopt_array($this->handle, [
            CURLOPT_COOKIEFILE => $jarFile,
            CURLOPT_COOKIEJAR => $jarFile,
        ]);
    }

    /**
     * Manually sets request cookie
     */
    public function setCookie(string $name, string $value)
    {
        $this->cookies[$name] = $value;

        $this->logger->debug(__METHOD__, ['name' => $name, 'value' => $value]);
    }

    /**
     * Send GET HTTP request for a given URL
     *
     * @param string $url
     * @param array $query
     * @return Response
     * @throws Nano\Http\ResponseException
     */
    public function get(string $url, array $query = []): Response
    {
        // add request params
        if (!empty($query) && is_array($query)) {
            $url .= '?' . http_build_query($query);
        }

        return $this->sendRequest(self::GET, $url);
    }

    /**
     * Send POST HTTP request for a given URL
     *
     * @param string $url
     * @param mixed|false $fields URL parameters
     * @return Response
     * @throws Nano\Http\ResponseException
     */
    public function post(string $url, $fields = false): Response
    {
        // add request POST fields
        if (is_array($fields)) {
            curl_setopt($this->handle, CURLOPT_POSTFIELDS, http_build_query($fields));
        } elseif (is_string($fields)) {
            curl_setopt($this->handle, CURLOPT_POSTFIELDS, $fields);
        }

        return $this->sendRequest(self::POST, $url);
    }

    /**
     * Send HEAD HTTP request for a given URL
     *
     * @param string $url
     * @param array $query
     * @return Response
     * @throws Nano\Http\ResponseException
     */
    public function head(string $url, array $query = []): Response
    {
        // add request params
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        return $this->sendRequest(self::HEAD, $url);
    }

    /**
     * Send HTTP request
     *
     * @param string $method
     * @param string $url
     * @return Response
     * @throws Nano\Http\ResponseException
     */
    private function sendRequest(string $method, string $url): Response
    {
        // send requested type of HTTP request
        curl_setopt($this->handle, CURLOPT_POST, false);
        curl_setopt($this->handle, CURLOPT_NOBODY, false);

        switch ($method) {
            case self::POST:
                curl_setopt($this->handle, CURLOPT_POST, true);
                break;

            case self::HEAD:
                // @see http://curl.haxx.se/mail/curlphp-2008-03/0072.html
                curl_setopt($this->handle, CURLOPT_NOBODY, true);
                break;

            case self::GET:
            default:
                // nop
        }

        curl_setopt($this->handle, CURLOPT_URL, $url);

        // set cookies
        // @see http://stackoverflow.com/questions/6453347/php-curl-and-setcookie-problem
        if (!empty($this->cookies)) {
            $cookies = [];
            foreach ($this->cookies as $key => $value) {
                $cookies[] = "{$key}={$value}";
            }

            curl_setopt($this->handle, CURLOPT_COOKIE, implode('; ', $cookies));
        }

        // set request headers
        curl_setopt($this->handle, CURLOPT_HTTPHEADER, $this->reqHeaders);

        // cleanup
        $this->reqHeaders = [];
        $this->respHeaders = [];

        $this->logger->info(__METHOD__ . ': sending a request', ['method' => $method, 'url' => $url]);

        // send request and grab response
        ob_start();
        $res = curl_exec($this->handle);
        $content = ob_get_clean();

        // get response
        if ($res === true) {
            // @see http://pl2.php.net/curl_getinfo
            $info = curl_getinfo($this->handle); //var_dump($info);

            // return HTTP response object
            $response = new Response();

            // set response code
            $response->setResponseCode($info['http_code']);

            // set response headers
            $response->setHeaders($this->respHeaders);

            // set response content
            $response->setContent($content);

            // set response location (useful for redirects)
            $response->setLocation($info['url']);

            $this->logger->info(__METHOD__ . ': request completed', [
                'method' => $method,
                'url' => $url,
                'response_code' => (int) $info['http_code'],
                'response_headers' => $this->respHeaders,
                'stats' => [
                    'total_time' => $info['total_time'] * 1000, // [ms]
                    'speed_download' => (int) $info['speed_download'],
                    'size_download' => (int) $info['size_download'],
                ]
            ]);
        } else {
            $e = new ResponseException(curl_error($this->handle), curl_errno($this->handle));

            $this->logger->error(__METHOD__. ': ' . $e->getMessage(), [
                'exception' => $e,
                'method' => $method,
                'url' => $url
            ]);

            throw $e;
        }

        return $response;
    }
}
