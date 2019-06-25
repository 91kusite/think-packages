<?php
namespace kusite\package\service;

use kusite\package\service\ParseService;

// use think\Container;

class CheckService
{

    /**
     * 检测组件是否存在
     * @Author   Martinsun<syh@sunyonghong.com>
     * @DateTime 2019-06-24
     * @param    string $package 组件名称
     * @return   boolean                                 [description]
     */
    public static function isExist($package): bool
    {
        $class = ParseService::parseName($package);

        return class_exists($class);
    }

    /**
     * 检测组件是否可调用,只有组件状态正常时可以用
     * @Author   Martinsun<syh@sunyonghong.com>
     * @DateTime 2019-06-24
     * @param    [type]                         $package [description]
     * @return   boolean                                 [description]
     */
    public static function isCallable($package): bool
    {
        if (!self::isExist($package)) {
            return false;
        }
        $class = ParseService::parseName($package);
        $pkg   = new $class();

        return '1' == $pkg->getConfigure('status');
    }

    /**
     * 组件注册,将组件统一注入到容器中,供全局使用
     * @Author   Martinsun<syh@sunyonghong.com>
     * @DateTime 2019-06-24
     * @param    [type]                         $package [description]
     * @return   [type]                                  [description]
     */
    public static function register($package)
    {
        $a = '调用获取A组件实例方法';
        if ($a) {
            // 正常逻辑
            // 在这还要调用C组件
            $c = '调用C组件实例';
            if ($c) {
                // C组件正常
            } else {
                // C组件异常
            }
        } else {
            // A组件异常
        }

        $a    = '调用获取A组件实例方法';
        $ret  = $a->get();
        $c    = '调用C组件实例';
        $data = $c->getRet($ret);
    }

}
