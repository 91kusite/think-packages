<?php
namespace kusite\package\service;

use kusite\package\service\ParseService;

class CheckService
{

    /**
     * 检测组件是否存在
     * @Author   Martinsun<syh@sunyonghong.com>
     * @DateTime 2019-06-24
     * @param    string $package 组件名称
     * @return   boolean
     */
    public static function isExist($package, $layer = null, $namespace = null): bool
    {
        $class = ParseService::parse($package, $layer, $namespace);

        return class_exists($class);
    }

    /**
     * 检测组件是否可调用,只有组件状态正常时可以用
     * @Author   Martinsun<syh@sunyonghong.com>
     * @DateTime 2019-06-24
     * @param    string $package   组件名称
     * @return   boolean
     */
    public static function isCallable($package, $layer = null, $namespace = null): bool
    {
        if (!self::isExist($package)) {
            return false;
        }

        $class = ParseService::parseName($package);
        $pkg   = new $class();

        return '1' == $pkg->getConfigure('status');
    }

    /**
     * 检测组件类是否存在
     * @Author   Martinsun<syh@sunyonghong.com>
     * @DateTime 2019-06-25
     * @param    string $package   组件名称
     * @param    string $layer     类分层名称 如controller model service等
     * @param    string $namespace 命名空间,可以是根命名,也可以是基于分层后的命名
     * @return   boolean
     */
    public static function isExistClass($package = null, $layer = null, $namespace = null): bool
    {
        $namespace = ParseService::parse($package, $layer, $namespace);

        return class_exists($namespace);
    }

}
