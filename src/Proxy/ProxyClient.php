<?php namespace Nano7\Sdk\Proxy;

use GuzzleHttp\Client;

class ProxyClient extends Client
{
    /**
     * Request how json.
     *
     * @param $method
     * @param string $uri
     * @param array $options
     * @return bool|mixed
     */
    public function json($method, $uri = '', array $options = [])
    {
        $http = $this->getConfig('http');
        $headers = $this->getConfig('headers');
        //'Accept' => 'application/json',

        $response = $this->request($method, $uri, $options);
        if (! $response->getStatusCode() == 200) {
            return false;
        }

        // Converte json to object
        $response = json_decode($response->getBody());
        if (is_null($response)) {
            return false;
        }

        return $response;
    }
}