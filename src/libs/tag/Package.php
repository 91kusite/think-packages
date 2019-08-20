<?php
namespace kusite\package\libs\tag;

use kusite\package\exception\NotCallableException;
use kusite\package\exception\NotFoundException;
use kusite\package\service\Service as packageService;
use think\Container;
use think\Loader;
use think\template\TagLib;

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
        // 获取组件模板
        try {
            $packageObj   = packageService::getInstance($package, 'controller');
            $templateFile = (isset($tag['template']) && $tag['template']) ? $tag['template'] : 'default';
            $hooks        = (isset($tag['hooks']) && $tag['hooks']) ? $tag['hooks'] : '';
            $name         = str_replace('_', '-', Loader::parseName($package, 0)) . '-' . $templateFile;

            $template = $packageObj->run($templateFile, $hooks);
            $template = '<' . $name . ' hooks="' . $hooks . '">' . $template . '</' . $name . '>';
        } catch (NotCallableException $e) {
            $template = '';
        } catch (NotFoundException $e) {
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
