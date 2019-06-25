<?php
namespace kusite\package\service;

use kusite\package\service\ParseService;
use kusite\package\service\CheckService;

class Service
{
    /**
     * 动态解析方法
     * @Author   Martinsun<syh@sunyonghong.com>
     * @DateTime 2019-06-25
     * @param    string $method 方法名
     * @param    array  $args   参数数组
     * @return   string|null
     */
    public static function __callStatic($method, $args)
    {
        if (strtolower(substr($method, 0, 13)) == 'getinstanceof') {
            $layer     = Loader::parseName(substr($method, 13)) ?: null;
            $namespace = isset($args[1]) ? $args[1] : null;

        } elseif (strtolower(substr($method, 0, 5)) == 'getof') {
            $layer     = Loader::parseName(substr($method, 5)) ?: null;
            $namespace = isset($args[1]) ? $args[1] : null;

        }

        return Service::getInstance($args[0], $layer, $namespace);
    }

    /**
     * 获取组件入口类
     * @Author   Martinsun<syh@sunyonghong.com>
     * @DateTime 2019-06-25
     * @param    [type]                         $package [description]
     * @return   [type]                                  [description]
     */
    public static function get($package = null)
    {
    	return Service::getInstance($package);
    }

    public static function getInstance($package = null, $layer = null, $namespace = null)
    {
        $classname = CheckService::isCallable($package);
        if(false === $classname){
        	// 不能调用,抛出错误
        	throw new Exception("Error Processing Request", 1);
        }
    }
}
