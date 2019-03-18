<?php

use think\facade\Env;
use think\Loader;

// 插件目录
define('PACKAGE_PATH', Env::get('root_path') . 'src' . DIRECTORY_SEPARATOR . 'packages' . DIRECTORY_SEPARATOR);

// 如果插件目录不存在则创建
if (!is_dir(PACKAGE_PATH)) {
    @mkdir(PACKAGE_PATH, 0755, true);
}

// 注册类的根命名空间
Loader::addNamespace('packages', PACKAGE_PATH);
