<?php

namespace Nano\Http;

/**
 * Wrapper for HTTP request response with headers and statistics
 */
class Response
{
    // response from HTTP server
    private $content;

    // URL response was fetched from
    private $location;

    // key / value list of HTTP headers to be emitted
    private $headers = [];

    // response HTTP code (default to 200 OK)
    private $responseCode = 200;

    /**
     * Set response content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * Get response content
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Cast to string returns response content
     */
    public function __toString()
    {
        return $this->getContent();
    }

    /**
     * Set headers
     */
    public function setHeaders($headers)
    {
        $this->headers = $headers;
    }

    /**
     * Get the value of given header
     */
    public function getHeader($name)
    {
        return $this->headers[$name] ?? null;
    }

    /**
     * Set response code
     */
    public function setResponseCode($responseCode)
    {
        $this->responseCode = $responseCode;
    }

    /**
     * Get response code
     */
    public function getResponseCode(): int
    {
        return $this->responseCode;
    }

    /**
     * Set response location (useful for redirects)
     */
    public function setLocation($location)
    {
        $this->location = $location;
    }

    /**
     * Get response location
     */
    public function getLocation()
    {
        return $this->location;
    }
}
