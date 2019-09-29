<?php
namespace kusite\package\libs\tag;

use kusite\package\exception\NotCallableException;
use kusite\package\exception\NotFoundException;
use kusite\package\service\Service as packageService;
use think\Container;
use think\Loader;
use think\template\TagLib;
use app\common\library\Components;

class Package extends TagLib
{
    /**
     * 定义标签列表
     */
    protected $tags = [
        // 标签定义： attr 属性列表 close 是否闭合（0 或者1 默认1） alias 标签别名 level 嵌套层次
        'show' => ['attr' => 'name,hooks,template', 'close' => 1], //闭合标签，默认为不闭合

    ];
    /**
     * 渲染组件方法
     * @Author   Martinsun<syh@sunyonghong.com>
     * @DateTime 2019-03-15
     * @param    array  $tag     标签参数
     * @param    str     $content 内容字符串
     * @return   string
     */
    public function tagShow($tag, $content)
    {
        $package = $tag['name'];
        $package = isset(Container::get('view')->$package) ? Container::get('view')->$package : $package;
        // 获取组件模板
        try {
            $class        = (isset($tag['controller']) && $tag['controller']) ? $tag['controller'] : $package;
            $packageObj   = packageService::getInstance($package, 'controller',$class);
            $templateFile = (isset($tag['template']) && $tag['template']) ? $tag['template'] : 'default';
            $hooks        = (isset($tag['hooks']) && $tag['hooks']) ? $tag['hooks'] : '';
            $name         = str_replace('_', '-', Loader::parseName($package, 0)) . '-' . $templateFile;

            $componentTpl = $packageObj->run($templateFile, $hooks);
            // 检测是否启用了组件容器,启用后,将本次的组件放入到容器中
            $useComponentsContainer = isset(Container::get('view')->useComponentsContainer) ? Container::get('view')->useComponentsContainer : true;
            if($useComponentsContainer === true){
                $template = '<' . $name . ' hooks="' . $hooks . '" :fileList="fileList"></' . $name . '>';
                $_static = (isset($tag['static']) && $tag['static']) ? explode(',',$tag['static']) : [];
                $static = [];
                if($_static){
                    foreach ($_static as $type) {
                        $static[$type] = $package.'-'.$templateFile;
                    }
                }
                // 注册组件到容器
                Container::get('components')->addComponents(function($component) use ($name,$componentTpl,$static){
                    $component->addComponents($name,$componentTpl,$static);
                });
            }else{
                $template = '<' . $name . ' hooks="' . $hooks . '">'.$componentTpl.'</' . $name . '>';
            }
            // dump(Container::get('view'));exit;
        } catch (NotCallableException $e) {
            $template = '';
        } catch (NotFoundException $e) {
            $template = '';
        }
        $parse = '<div ';
        unset($tag['name']);
        foreach ($tag as $key => $value) {
            if(in_array($key, ['static','hooks'])){
                continue;
            }
            $parse .= $key . '="' . $value . '"';
        }
        $parse .= ">" . $template;
        $parse .= $content;
        $parse .= "</div>";

        return $parse;
    }

}
