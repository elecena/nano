<?php

use Nano\Http\Response;

/**
 * Class with helper methods for HttpClient
 */
class Http
{

    /**
     * Send GET HTTP request for a given URL
     *
     * @param string $url
     * @param array $query
     * @return Nano\Http\Response
     * @throws Nano\Http\ResponseException
     */
    public static function get(string $url, array $query = []): Response
    {
        $client = new HttpClient();
        $ret = $client->get($url, $query);

        $client->close();
        return $ret;
    }

    /**
     * Send POST HTTP request for a given URL
     *
     * @param string $url
     * @param array $fields
     * @return Nano\Http\Response
     * @throws Nano\Http\ResponseException
     */
    public static function post(string $url, array $fields = []): Response
    {
        $client = new HttpClient();
        $ret = $client->post($url, $fields);

        $client->close();
        return $ret;
    }

    /**
     * Send HEAD HTTP request for a given URL
     *
     * @param string $url
     * @param array $query
     * @return Nano\Http\Response
     * @throws Nano\Http\ResponseException
     */
    public static function head(string $url, array $query = []): Response
    {
        $client = new HttpClient();
        $ret = $client->head($url, $query);

        $client->close();
        return $ret;
    }
}
