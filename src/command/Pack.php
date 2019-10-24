<?php
namespace kusite\package\command;

use app\common\helper\util\FileUtil;
use think\Console;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\Exception;
use think\facade\Env;

class Pack extends Command
{
    const DS = DIRECTORY_SEPARATOR;

    /**
     * 打包组件
     * @Author   Martinsun<syh@sunyonghong.com>
     * @DateTime 2019-10-09
     * @return   [type]                         [description]
     */
    protected function configure()
    {
        $this->setName('package:pack')
            ->addArgument('name', Argument::REQUIRED, 'set package\'s name to pack.', null)
            ->addOption('title', null, Option::VALUE_REQUIRED, 'set package\'s title.', null)
            ->addOption('rversion', 'r', Option::VALUE_REQUIRED, 'set package\'s version.', null)
            ->addOption('system', null, Option::VALUE_OPTIONAL, 'set system\'s packages.', 0)
            ->addOption('password', 'p', Option::VALUE_OPTIONAL, 'set package\'s read password.', null)
            ->addOption('outpath', null, Option::VALUE_OPTIONAL, 'set package\'s outpath.', null)
            ->setDescription('Pack package.')
            ->setHelp(<<<EOT
The <info>package:pack</info> command pack the package.

<info>php console package:pack packages_name --system=1 --title='title' -r 1.0.0</info>

EOT
            );
    }

    protected function execute(Input $input, Output $output)
    {
        // 组件包检测
        $zip_path = Env::get('root_path') . 'public' . self::DS . 'packages';
        if ($input->hasOption('outpath')) {
            $zip_path .= self::DS . $input->getOption('outpath');
        }
        $package_name = trim($input->getArgument('name'));

        if (!$package_name) {
            $output->writeln('<error>Please set name.</error>');
            return false;
        }

        // 是否设置了title和version
        if (!$input->hasOption('title') || !$input->hasOption('rversion')) {
            $output->writeln('<error>Please set title and version.</error>');
            return false;
        }
        // 临时目录
        $temppath = Env::get('runtime_path') . 'package' . self::DS . strtolower($package_name);
        FileUtil::createDir($temppath);
        // 整理需要打包的文件并输出到临时目录
        $dirs = $this->getCopySources();
        $this->copySources($this->getCopySources(), $temppath, $package_name);
        // 检测当前包是否在/src/packages中存在前端部分代码(复制非已经打包的目录以及文件)
        $package_path = PACKAGE_PATH . $package_name;
        if (file_exists($package_path)) {
            // 打开目录
            $dh = opendir($package_path);
            // 循环读取目录
            while (($file = readdir($dh)) !== false) {
                // 过滤掉当前目录'.'和上一级目录'..'
                if ($file == '.' || $file == '..') {
                    continue;
                }
                // 目录整体复制
                if (is_dir($package_path . self::DS . $file) && !is_dir($temppath . self::DS . $file)) {
                    FileUtil::copyDir($package_path . self::DS . $file, $temppath . self::DS . $file);
                    continue;
                }

                // 文件单个复制
                if (!is_file($temppath . self::DS . $file)) {
                    FileUtil::copyFile($package_path . self::DS . $file, $temppath . self::DS . $file);
                    continue;
                }
            }
        }
        // 生成.ini配置文件(强制覆盖)
        $ini = fopen($temppath . self::DS . '.ini', 'w+');
        fwrite($ini, "status = 1\r\n");
        if ($input->hasOption('system') && $input->getOption('system') == 1) {
            fwrite($ini, "is_system = 1\r\n");
        }
        fclose($ini);
        // 生成当前包名的入口文件(不存在时)
        if (!file_exists($temppath . self::DS . parse_name($package_name, 1) . '.php')) {
            $stub = file_get_contents(__DIR__ . self::DS . 'stubs' . self::DS . 'package_install.stub');

            $stub = str_replace(['{%className%}', '{%namespace%}', '{%name%}', '{%title%}', '{%version%}'], [
                parse_name($package_name, 1),
                parse_name($package_name, 1, false),
                parse_name($package_name, 1, false),
                $input->getOption('title'),
                $input->getOption('rversion'),

            ], $stub);

            file_put_contents($temppath . self::DS . parse_name($package_name, 1) . '.php', $stub);
        }

        // 执行打包命令
        $res = $this->packToPackages($output, $temppath, $zip_path, parse_name($package_name, 1, false), $input->getOption('rversion'));
        if ($res === false) {
            $output->writeln('<error>Pack ' . parse_name($package_name, 1, false) . ' error.</error>');
            return false;
        }

        while (is_dir($temppath)) {
            try {
                // 清理临时目录
                FileUtil::unlinkDir($temppath);
            } catch (\Exception $e) {}
        }

        $output->writeln('<info>Pack ' . parse_name($package_name, 1, false) . ' success!</info>');
    }

    /**
     * 打包组件
     * @Author   Martinsun<syh@sunyonghong.com>
     * @DateTime 2019-10-09
     * @param    [type]                         $output       [description]
     * @param    [type]                         $savepath     [description]
     * @param    [type]                         $package_name [description]
     * @param    [type]                         $version      [description]
     * @return   [type]                                       [description]
     */
    protected function packToPackages($output, $packpath, $savepath, $package_name, $version)
    {
        $output->writeln("<info>Please waiting...</info>");
        // 备份当前组件
        $output->writeln("<info>Pack package: " . $package_name . "...</info>");
        $command_params = [];
        // 包名
        $packname         = $package_name . '.' . $version . '.zip';
        $command_params[] = $packname;
        // 打包路径
        $command_params[] = str_replace(Env::get('root_path'), '', $packpath);
        // 包保存路径
        $command_params[] = '--outpath=' . str_replace(Env::get('root_path'), '', $savepath) . self::DS;
        // 执行解包
        try {
            Console::call('zip:pack', $command_params);
            // 检测是否已经备份
            if (is_file($savepath . self::DS . $packname)) {
                return $packname;
            }
        } catch (Exception $e) {
            $output->writeln("<error>Backup error package:" . $package_name . ".Please try again.</error>");
        }
        return false;
    }

    /**
     * 获取待复制的目录
     * @Author   Martinsun<syh@sunyonghong.com>
     * @DateTime 2019-09-19
     * @return   [type]                         [description]
     */
    public function getCopySources()
    {
        // 下标为replace_开头的,会替换root_path,并截取其后的路径为输出目录结构,否则直接取当前下标为输出目录
        return [
            'application'                  => Env::get('app_path'),
            'public' . self::DS . 'static' => Env::get('root_path') . 'public' . self::DS . 'static' . self::DS,
        ];
    }

    /**
     * 复制资源
     * @Author   Martinsun<syh@sunyonghong.com>
     * @DateTime 2019-10-23
     * @param    [type]                         $dirs         [description]
     * @param    [type]                         $temppath     [description]
     * @param    [type]                         $package_name [description]
     * @return   [type]                                       [description]
     */
    protected function copySources($dirs, $temppath, $package_name)
    {
        foreach ($dirs as $root => $dir) {
            if (!is_dir($dir)) {
                continue;
            }
            // 打开目录
            $dh = opendir($dir);
            // 循环读取目录
            while (($file = readdir($dh)) !== false) {

                // 过滤掉当前目录'.'和上一级目录'..'
                if ($file == '.' || $file == '..') {
                    continue;
                }

                $current = $dir . $file;
                if (is_dir($current)) {
                    // 检测当前文件是否与包名同名或小写目录名称
                    if ($file == parse_name($package_name, 1, false) || $file == parse_name($package_name)) {
                        FileUtil::copyDir($current, $temppath . self::DS . $root . self::DS . $file);
                        continue;
                    }
                    // 否则就遍历目录
                    $this->copySources([$root . self::DS . $file => $current . self::DS], $temppath, $package_name);
                    continue;
                } else {
                    $filename = pathinfo($file, PATHINFO_FILENAME);
                    // 文件同名或小写
                    if ($filename == parse_name($package_name, 1, false) || $filename == parse_name($package_name)) {
                        FileUtil::copyFile($current, $temppath . self::DS . $root . self::DS . $file, true);
                        continue;
                    }
                }
            }
            // 关闭目录
            closedir($dh);
        }
    }

}
