<?php namespace Nano7\Sdk;

use Nano7\Support\Arr;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\SetCookie;
use Psr\Http\Message\ResponseInterface;

class SdkClient
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var array
     */
    protected $endpoints = [
        'production' => '', // http://api.com/{version}
        'sandbox' => '', // http://api.sandbox.api.com/{version}
    ];

    /**
     * @var array
     */
    protected $headers = [
        'Accept' => 'application/json',
    ];

    /**
     * @var array
     */
    protected $services = [];

    /**
     * @param array $config
     */
    public function __construct($config = [])
    {
        $this->config = $config;

        $config_http = Arr::get($config, 'http', ['headers' => $this->headers]);

        $this->client = new Client($config_http);

        // Guardar access token original
        //$accessToken = $this->config('access_token', false);
        //if ($accessToken !== false) {
        //    $this->config(['access_token_original' => $accessToken]);
        //}
    }

    /**
     * @param $name
     * @param $callback
     * @return Service
     */
    protected function toService($name, $callback)
    {
        // Veriifcar se serviço já existe
        if (array_key_exists($name, $this->services)) {
            return $this->services[$name];
        }

        // Criar servico
        return $this->services[$name] = call_user_func_array($callback, []);
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed
     */
    public function config($key, $default = null)
    {
        if (is_array($key) && is_null($default)) {
            $this->config = array_merge($this->config, $key);
            return true;
        }

        return Arr::get($this->config, $key, $default);
    }

    /**
     * @param null $part
     * @param array $params
     * @return string
     */
    public function uri($part = null, $params = [])
    {
        $params = implode('/', $params);
        $params = ($params == '') ? '' : sprintf('/%s', $params);

        $resource = is_null($part) ? '%s%s' : '%s/%s%s';

        $url = sprintf($resource, $this->getEndPoint(), $part, $params);

        return $url;
    }

    /**
     * Retorna o endpoint pelo ambiente.
     *
     * @return string
     * @throws \Exception
     */
    protected function getEndPoint()
    {
        // Verificar se foi informado o endpoitn explicito
        $url = $this->config('endpoint');
        if (! is_null($url)) {
            return $url;
        }

        $env = $this->config('environment', 'production');
        if (! array_key_exists($env, $this->endpoints)) {
            throw new \Exception("Environment api client invalid [$env]");
        }

        return $this->endpoints[$env];
    }

    /**
     * @param $method
     * @param string $uri
     * @param array $options
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function requestAsync($method, $uri = '', array $options = [])
    {
        $this->prepareOptions($method, $options);

        $response = $this->client->request($method, $uri, $options);

        $this->testResponseError($response);

        return $response;
    }

    /**
     * @param $method
     * @param string $uri
     * @param array $options
     * @return mixed
     */
    public function request($method, $uri = '', array $options = [])
    {
        $this->prepareOptions($method, $options);

        $response = $this->client->request($method, $uri, $options);

        $this->testResponseError($response);

        return $response;
    }

    /**
     * @param $options
     */
    protected function prepareOptions($method, &$options)
    {
        // Send XDebug
        $xdebug = $this->config('xdebug', false);
        if ($xdebug !== false) {
            if (! isset($options['query'])) {
                $options['query'] = [];
            }
            $options['query']['XDEBUG_SESSION_START'] = $xdebug;
        }

        // AccessToken
        $accessToken = $this->config('access_token', false);
        if ($accessToken !== false) {
            if (! isset($options['query'])) {
                $options['query'] = [];
            }
            $options['query']['access_token'] = $accessToken;
        }
    }

    /**
     * @param ResponseInterface $response
     * @return null|array
     */
    public function responseJson(ResponseInterface $response)
    {
        $json = json_decode($response->getBody(), true);
        if (is_null($json)) {
            $message = trim($response->getBody());
            throw new \Exception($message);
        }

        return $json;
    }

    /**
     * Test if error.
     *
     * @param ResponseInterface $response
     * @return bool
     * @throws \Exception
     */
    protected function testResponseError(ResponseInterface $response)
    {
        // Verificar error http
        if (! $response->getStatusCode() == 200) {
            throw new \Exception("Error response: " . $response->getStatusCode());
        }

        // Verificar error via json
        $json = json_decode($response->getBody());
        if (is_null($json)) {
            return true;
        }

        if (! isset($json->error)) {
            return true;
        }

        $message = isset($json->error->message) ? $json->error->message : '???';
        $code = isset($json->error->code) ? $json->error->code : 0;

        // Verificar se tem erros de atributos
        if (isset($json->error->errors)) {
            $info = '';
            foreach ((array) $json->error->errors as $attr => $msgs) {
                $info .= " - $attr:\r\n";
                foreach ($msgs as $msg) {
                    $info .= "   - $msg\r\n";
                }
            }

            $message = sprintf("%s\r\n%s", $message, $info);
        }

        throw new \Exception($message, $code);
    }
}