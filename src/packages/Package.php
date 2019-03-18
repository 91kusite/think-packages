<?php

namespace think\packages;

use think\facade\Config;

class Package
{
    /**
     * 当前视图模型
     * @var \think\view
     */
    protected $view;
    /**
     * 当前模板目录
     * @var string
     */
    protected $viewPath;

    /**
     * 定义组件配置
     * @var array
     */
    protected $packages;
    /**
     * 当前组件名称
     * @var string
     */
    protected $packageName;
    /**
     * 架构方法
     * @Author   Martinsun<syh@sunyonghong.com>
     * @DateTime 2019-03-15
     */
    public function __construct()
    {
        $this->view        = app('view');
        $this->packageName = $this->getConfigure('name');
        $this->viewPath    = PACKAGE_PATH . $this->packageName . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR;
        $this->packages    = isset($this->view->packages) ? $this->view->packages : [];
        // 执行初始化操作
        $this->initialize();
    }

    /**
     * 初始化方法
     * @Author   Martinsun<syh@sunyonghong.com>
     * @DateTime 2019-03-15
     * @return   [type]                         [description]
     */
    protected function initialize()
    {}

    /**
     * 获取配置
     * @Author   Martinsun<syh@sunyonghong.com>
     * @DateTime 2019-03-15
     * @param    [type]                         $key [description]
     * @return   [type]                              [description]
     */
    public function getConfigure($key = null)
    {
        $config = $this->configure();
        if (is_null($key)) {
            return $config;
        }
        return isset($config[$key]) ? $config[$key] : null;
    }

    /**
     * 加载模板输出
     * @access protected
     * @param  string $template 模板文件名
     * @param  array  $vars     模板输出变量
     * @param  array  $config   模板参数
     * @return mixed
     */
    protected function fetch($template = '', $vars = [], $config = [])
    {
        // 指定模板时,必须带后缀
        $template = $template ?: 'default.html';
        if ('' == pathinfo($template, PATHINFO_EXTENSION)) {
            // 获取模板文件名
            $template = $template . '.' . Config::get('template.view_suffix');
        }
        // 存在自定义组件配置时进行合并
        if (isset($this->packages[$this->packageName])) {
            $config                             = array_merge($this->getConfigure('vars'), $this->packages[$this->packageName]);
            $this->packages[$this->packageName] = $config;
        } else {
            $this->packages[$this->packageName] = $this->getConfigure('vars');
        }
        $this->assign($this->packages);
        return $this->view->fetch($this->viewPath . $template, $vars, $config);
    }

    /**
     * 模板变量赋值
     * @access protected
     * @param  mixed $name  要显示的模板变量
     * @param  mixed $value 变量的值
     * @return $this
     */
    protected function assign($name, $value = '')
    {
        $this->view->assign($name, $value);

        return $this;
    }
}