<?php namespace Nano7\Sdk;

class Collection extends \Illuminate\Support\Collection
{
    /**
     * @param array $items
     * @param null $callback
     */
    public function __construct($items = [], $callback = null)
    {
        if (is_null($callback)) {
            parent::__construct($items);
        }

        $list = [];
        foreach ($items as $k => $v) {
            $list[] = call_user_func_array($callback, [$k, $v]);
        }

        parent::__construct($list);
    }
}