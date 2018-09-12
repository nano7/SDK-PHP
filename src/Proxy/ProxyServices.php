<?php namespace Nano7\Sdk\Proxy;

class ProxyServices
{
    /**
     * @var ProxyClient
     */
    protected $client;

    /**
     * @var ProxyStorage
     */
    protected $storage;

    /**
     * List of services options.
     * @var array
     */
    protected $services = [];

    /**
     * List of proxies.
     *
     * @var array
     */
    protected $proxies = [];

    /**
     * @param ProxyStorage $storage
     * @param array $httpConfig
     */
    public function __construct(ProxyStorage $storage, $httpConfig = [])
    {
        $this->storage = $storage;
        $this->client = new ProxyClient($httpConfig);
    }

    /**
     * Resolver by services and proxies.
     *
     * @param array $request
     * @return bool|mixed|null
     */
    public function resolve(array $request)
    {
        // Verificar se consegue resolver pelo cache
        $cache = $this->storage->resolveByCache($request);
        if (! is_null($cache)) {
            return $cache;
        }

        $services = $this->getListServicesOptions();
        foreach ($services as $serv_id => $info) {
            $response = $this->resolveService($info, $request);

            // Register status
            $this->storage->setStatus($serv_id, ($response !== false));

            // Verificar se retornou positivamente
            if ($response !== false) {
                $response->source = $serv_id;

                // Guardar no controle de cache
                $this->storage->storeInCache($request, $response->toStore());

                // Marcar como atual
                $this->storage->setCurrent($serv_id);

                return $response->toResponse();
            }
        }

        return false;
    }

    /**
     * Resolve service.
     *
     * @param $info
     * @param $request
     * @return bool|ServiceResponde
     */
    protected function resolveService($info, $request)
    {
        try {
            $url = $info['url'];
            $callback = $info['callback'];

            $ret = call_user_func_array($callback, [$this->client, $url, $request]);
            if (! ($ret instanceof ServiceResponde)) {
                throw new \Exception("Return is not ServiceResponde");
            }

            return $ret;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get list of list services and proxies ordened.
     *
     * @return array
     */
    protected function getListServicesOptions()
    {
        // Montar lista de serviços com o proxy
        $list = $this->services;
        foreach ($this->services as $service_id => $service_info) {
            foreach ($this->proxies as $proxy_id => $proxy) {
                $source = sprintf('%s:%s', $service_id, $proxy_id);
                $info = $service_info;
                $info['url'] = str_replace('{{url}}', urlencode($info['url']), $proxy);
                $list[$source] = $info;
            }
        }

        $current_service = $this->storage->getCurrent();
        $enabled = (! array_key_exists($current_service, $list));

        $ret = [];
        $before = [];

        foreach ($list as $serv_id => $srv_url) {
            if ($enabled || ($serv_id == $current_service)) {
                $ret[$serv_id] = $srv_url;
                $enabled = true;
            } else {
                $before[$serv_id] = $srv_url;
            }
        }

        // Adicionar before no final
        foreach ($before as $id => $value) {
            $ret[$id] = $value;
        }

        return $ret;
    }

    /**
     * Add service.
     *
     * @param $service_id
     * @param $url
     * @param $callback
     */
    public function addService($service_id, $url, $callback)
    {
        $info = [
            'url' => $url,
            'callback' => $callback,
        ];

        $this->services[$service_id] = $info;
    }

    /**
     * Add proxy.
     *
     * @param $proxy_id
     * @param $url
     */
    public function addProxy($proxy_id, $url)
    {
        $this->proxies[$proxy_id] = $url;
    }
}