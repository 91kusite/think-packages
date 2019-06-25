<?php
namespace kusite\package\libs;

use think\facade\Cache;
use think\Loader;

abstract class Install
{
    public function __construct()
    {
        // 初始化配置
        $this->setConfigure();
    }

    /**
     * 获取组件名称
     * @Author   Martinsun<syh@sunyonghong.com>
     * @DateTime 2019-06-24
     * @return string
     */
    public function getPackageName(): string
    {
        $config = $this->configure();

        return $config['name'] ?: '';
    }

    /**
     * 设置组件配置,兼容自定义配置
     * @Author   Martinsun<syh@sunyonghong.com>
     * @DateTime 2019-06-24
     * @param array
     */
    public function setConfigure($package = null): array
    {
        if (is_null($package)) {
            $package = $this->getPackageName();
        }
        $config = Cache::get('package_' . $package);
        if ($config) {
            return $config;
        }

        $inifile_path = PACKAGE_PATH . $package . DIRECTORY_SEPARATOR . '.ini';
        $object       = Loader::factory('ini', '\\think\\config\\driver\\', $inifile_path);
        $ini          = $object->parse() ?: [];
        $config       = array_merge($ini, $this->configure());
        Cache::set('package_' . $package, $config);

        return $config;

    }
    /**
     * 获取配置
     * @Author   Martinsun<syh@sunyonghong.com>
     * @DateTime 2019-06-24
     * @param    string $key 配置名称,不传获取所有
     * @return   string|array
     */
    public function getConfigure($key = null)
    {
        $package = $this->getPackageName();
        $config  = Cache::get('package_' . $package);
        if (!$config) {
            $config = $this->setConfigure($package);
        }

        if (is_null($key)) {
            return $config;
        }

        return isset($config[$key]) ? $config[$key] : null;
    }
}
