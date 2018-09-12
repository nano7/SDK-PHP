<?php namespace Nano7\Sdk\Proxy;

class ServiceResponde
{
    /**
     * @var array
     */
    protected $data = [];

    /**
     * @param $data
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function toStore()
    {
        return $this->data;
    }

    /**
     * @return array
     */
    public function toResponse()
    {
        return $this->data;
    }
}