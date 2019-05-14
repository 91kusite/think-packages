<?php
namespace think\taglib\packages;

use think\template\TagLib;
use think\Container;

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
        $package = Container::get('view')->$package;
        // TODO:其他操作
        // 获取组件模板
        $class = 'packages\\' . $package . '\\' . ucfirst($package);
        if (class_exists($class)) {
            $packageObj   = new $class();
            $templateFile = (isset($tag['template']) && $tag['template']) ? $tag['template'] : 'default';
            $hooks        = (isset($tag['hooks']) && $tag['hooks']) ? $tag['hooks'] : '';
            $template     = $packageObj->run($templateFile, $hooks);
        } else {
            $template = '';
        }
        $parse = '<div ';
        unset($tag['name']);
        foreach ($tag as $key => $value) {
            $parse .= $key . '="' . $value . '"';
        }
        $parse .= ">" . $template;
        $parse .= $content;
        $parse .= "</div>";

        return $parse;
    }

}
