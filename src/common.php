<?php

use think\facade\Env;
use think\facade\Response;
use think\facade\Route;
use think\Loader;
use think\facade\Request;

// 插件目录
define('PACKAGE_PATH', Env::get('root_path') . 'src' . DIRECTORY_SEPARATOR . 'packages' . DIRECTORY_SEPARATOR);

// 如果插件目录不存在则创建
if (!is_dir(PACKAGE_PATH)) {
    @mkdir(PACKAGE_PATH, 0755, true);
}

// 注册类的根命名空间
Loader::addNamespace('packages', PACKAGE_PATH);
// 注册组件接口路由
Route::any('/packages/:type/:package/:service/:action', function ($type, $package, $service, $action) {
    $classname = 'packages\\' . $package . '\\' . $type . '\\' . ucfirst($service);
    if ($classname) {
        $class = new $classname();
        if (is_callable([$class, $action])) {
            Request::instance()->setModule($package)->setController($service)->setAction($action);
            return $class->$action();
        }
    }

    return Response::create(['data' => ['error_code' => 404], 'code' => 0, 'msg' => '请求组件不存在或异常'], 'json', 200);
});


function short($str){
 
    $code = floatval(sprintf('%u', crc32($str)));
 
    $sstr = '';
 
    while($code){
        $mod = fmod($code, 62);
        if($mod>9 && $mod<=35){
            $mod = chr($mod + 55);
        }elseif($mod>35){
            $mod = chr($mod + 61);
        }
        $sstr .= $mod;
        $code = floor($code/62);
    }
 
    return $sstr;
 
}
// // spm = 网页key
// Route::any('/sp/admin/:spm',function($spm){
//     echo short('controller.adminRule.Index.adminList');exit;
//     echo $spm;exit;

// })->pattern(['spm'=>'[\d\w\.]+']);
// 注册组件后台访问路由
Route::any('/sp/admin/:type/:package/:service/:action', function ($type, $package, $service, $action) {
    // echo $type."<br />";
    // $str = $type.'.'.$package.'.'.$service.'.'.$action;
    // $code = bin2hex($str);
    // echo $code."<br />";
    // echo hex2bin($code)."<br />";
    // exit;
    $classname = 'packages\\' . $package . '\\' . $type . '\\' . ucfirst($service);
    if ($classname) {
        $class = new $classname();
        if (is_callable([$class, $action])) {
            return $class->$action();
        }
    }

    return Response::create(['data' => ['error_code' => 404], 'code' => 0, 'msg' => '请求组件不存在或异常'], 'json', 200);
})->middleware([app\admin\middleware\CheckAxiosRequest::class,app\admin\middleware\BindLoginUser::class,kusite\package\libs\middleware\RegisterPackage::class]);