<?php namespace Nano7\Sdk\Proxy;

interface ProxyStorage
{
    /**
     * Resolve values by cache.
     *
     * @param array $request
     * @return mixed|null
     */
    public function resolveByCache(array $request);

    /**
     * Store values by request.
     *
     * @param array $request
     * @param $values
     * @return mixed
     */
    public function storeInCache(array $request, $values);

    /**
     * Store status of service.
     *
     * @param $service_id
     * @param $status
     * @return mixed
     */
    public function setStatus($service_id, $status);

    /**
     * Get status of service.
     *
     * @param $service_id
     * @param null $default
     * @return mixed
     */
    public function getStatus($service_id, $default = null);

    /**
     * Store current service or proxy.
     *
     * @param $service_id
     * @return bool
     */
    public function setCurrent($service_id);

    /**
     * Get current service or proxy.
     *
     * @return string|null
     */
    public function getCurrent();
}