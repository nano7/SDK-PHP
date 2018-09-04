<?php namespace Nano7\Sdk;

use Nano7\Foundation\Support\Arr;

abstract class Service
{
    /**
     * @var SdkClient
     */
    protected $client;

    /**
     * @var string
     */
    protected $baseEndPoint = '';

    /**
     * @param SdkClient $client
     * @param array $config
     */
    public function __construct(SdkClient $client, $config = [])
    {
        $this->client = $client;

        $this->baseEndPoint = Arr::get($config, 'endpoint', $this->baseEndPoint);
    }

    /**
     * @param string $part
     * @return string
     */
    protected function uri($part = '', $params = [])
    {
        $params = implode('/', $params);
        $params = ($params == '') ? '' : sprintf('/%s', $params);

        $resource = (is_null($part) || (trim($part) == '')) ? '%s' : '%s/%s%s';

        $url = sprintf($resource, $this->baseEndPoint, $part, $params);

        return $url;
    }
}