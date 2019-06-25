<?php
namespace kusite\package\service;

use kusite\package\service\ParseService;
use kusite\package\service\CheckService;
use kusite\package\exception\NotCallableException;
use kusite\package\exception\NotFoundException;

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

    /**
     * [getInstance description]
     * @Author   Martinsun<syh@sunyonghong.com>
     * @DateTime 2019-06-25
     * @param    string $package   组件名称
     * @param    string $layer     类分层名称 如controller model service等
     * @param    string $namespace 命名空间,可以是根命名,也可以是基于分层后的命名
     * @return   [type]                                    [description]
     */
    public static function getInstance($package = null, $layer = null, $namespace = null)
    {
        $classname = CheckService::isCallable($package);
        if(false === $classname){
        	// 不能调用,抛出错误
        	throw new NotCallableException('目标组件不可调用');
        }

        $namespace = ParseService::parse($package, $layer, $namespace);

        // 当前组件模块实例
        if(false == CheckService::isExistClass(null, null, $namespace )){
            throw new NotFoundException('目标组件内不存在该接口');
        }

        return new $namespace;
    }
}
