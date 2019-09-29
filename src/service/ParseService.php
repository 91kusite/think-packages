<?php
namespace kusite\package\service;

use think\Loader;

// use think\Container;
/**
 *
 */
class ParseService
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
        if (strtolower(substr($method, 0, 7)) == 'parseto') {
            $layer     = Loader::parseName(substr($method, 7)) ?: null;
            $namespace = isset($args[1]) ? $args[1] : null;

            return ParseService::parse($args[0], $layer, $namespace);
        } elseif (strtolower($method) == 'parsename') {

            $namespace = isset($args[1]) ? $args[1] : null;

            return ParseService::parse($args[0], null, $namespace);
        }

        return null;
    }
    /**
     * 解析组件类命名空间
     * @Author   Martinsun<syh@sunyonghong.com>
     * @DateTime 2019-06-25
     * @param    string $package   组件名称
     * @param    string $layer     类分层名称 如controller model service等
     * @param    string $namespace 命名空间,可以是根命名,也可以是基于分层后的命名
     * @return   string
     */
    public static function parse($package = null, $layer = null, $namespace = null): string
    {
        // 指定了完整的根命名空间
        if (substr($namespace, 0, 1) == '\\') {
            return $namespace;
        }
        // 组装命名空间
        $classname = '\\packages';
        !is_null($package) && $classname .= '\\' . $package;
        !is_null($layer) && $classname .= '\\' . $layer;
        $namespace = is_null($namespace) ? ucfirst($package) : ucfirst($namespace);
        $classname .= '\\' . $namespace;

        return $classname;
    }

    /**
     * 解析命名空间
     * @Author   Martinsun<syh@sunyonghong.com>
     * @DateTime 2019-06-25
     * @param    string $namespace 所需解析的命名空间完整信息
     * @param    获取下标的类 $key 可选 name:组件名称 layer:组件所在分层 class:当前调用的类
     * @return
     */
    public static function parseNamespace($namespace, $key = null)
    {
        // 指定了完整的根命名空间
        if (substr($namespace, 0, 1) == '\\' || substr($namespace, 0, 8) == 'packages') {
            // 完成的类
            $_explode = explode('\\', $namespace);
            $ret      = [
                'name'  => $_explode[1],
                'layer' => $_explode[2],
                'class' => end($_explode),
            ];
            return $key ? (isset($ret[$key]) ? $ret[$key] : null) : $ret;
        } else {
            return null;
        }
    }

}
