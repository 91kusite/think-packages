<?php
namespace kusite\package\libs;

use app\admin\controller\Admin;

/**
 * 后台组件基类
 */
class AdminPackage extends Admin
{
    /**
     * 设置不自动加载静态资源
     * @var boolean
     */
    protected $autoload_static = false;

    /**
     * 设置组件静态文件资源
     * @Author   Martinsun<syh@sunyonghong.com>
     * @DateTime 2019-09-09
     * @param    [type]                         $files [description]
     */
    public function setPackageStatic($files = [])
    {
        // 配置加载组件的静态资源
        $css                                    = isset($files['css']) ? implode('@', $files['css']) : '';
        $css && $this->assignList['packageCss'] = '/static/libs/mini/packages.php?&m=' . $css;
        $js                                     = isset($files['js']) ? implode('@', $files['js']) : '';
        $js && $this->assignList['packageJs']   = '/static/libs/mini/packages.php?t=js&m=' . $js;
    }
}
