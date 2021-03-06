<?php
namespace kusite\package\command;

use app\common\helper\util\FileUtil;
use kusite\package\libs\Db as PackageDb;
use think\Console;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\Exception;
use think\facade\Env;

class Install extends Command
{
    const DS = DIRECTORY_SEPARATOR;
    /**
     * 安装组件包
     * @Author   Martinsun<syh@sunyonghong.com>
     * @DateTime 2019-03-18
     */
    protected function configure()
    {
        $this->setName('package:install')
            ->addArgument('name', Argument::REQUIRED, 'set package\'s name to install.', null)
            ->addOption('password', 'p', Option::VALUE_OPTIONAL, 'set package\'s read password.', null)
            ->addOption('rversion', 'r', Option::VALUE_OPTIONAL, 'set package\'s version.', null)
            ->addOption('yes', 'y', Option::VALUE_NONE, 'make sure.', null)
            ->addOption('path', null, Option::VALUE_OPTIONAL, 'set package\'s path.', null)
            ->addOption('back', null, Option::VALUE_OPTIONAL, 'set package\'s backup number.', null)
            ->setDescription('Install package.')
            ->setHelp(<<<EOT
The <info>package:install</info> command installs the package, which can install the specified version or use backup installation

<info>php console package:install test</info>
<info>php console package:install test -p 123456</info>
<info>php console package:install test -r 1.0.0</info>
<info>php console package:install test --back 20190227110637</info>

EOT
            );
    }
    protected function execute(Input $input, Output $output)
    {
        // 组件包检测
        $zip_path = Env::get('root_path') . 'public' . self::DS . 'packages';
        if ($input->hasOption('path')) {
            $zip_path .= self::DS . $input->getOption('path');
        }
        $package_name = trim($input->getArgument('name'));

        if (!$package_name) {
            $output->writeln('<error>Please set package\'s name.</error>');
            return false;
        }

        $is_sure = $packname = false;
        // 检测是否已经安装
        if (is_dir(PACKAGE_PATH . $package_name)) {
            $output->writeln('<info>Nothing to do,because the package of ' . $package_name . ' is installed.</info>');
            // 提示是否覆盖
            if (!$output->confirm($input, 'Already installed, confirm replacement?', false)) {
                $output->writeln("<info>User cancel.</info>");
                return false;
            }
            // 备份已安装版本
            $packname = $this->backupToPackages($output, $zip_path, $package_name);

            if ($packname === false) {
                return false;
            }
            $is_sure = true;
        }

        $zip_name = null;
        // 检测是否备份安装
        if (!$input->hasOption('back')) {
            // 版本规定
            if ($input->hasOption('rversion')) {
                // 指定版本
                $list = glob($zip_path . self::DS . $package_name . '.' . $input->getOption('rversion') . '.zip');
            } else {
                // 获取最新版本
                $list = glob($zip_path . self::DS . $package_name . '.*');
            }
            // 取得最新版本或指定版本
            if ($list) {
                rsort($list);
                $zip_name = basename($list[0]);
            }
        } else {
            // 拼接备份包存放位置
            $zip_name = $package_name . '.back.' . $input->getOption('back');
        }
        // zip包识别
        (stripos($zip_name, '.zip') === false) && $zip_name .= '.zip';
        // 检测安装包是否存在
        if (!$zip_name || !is_file($zip_path . self::DS . $zip_name)) {
            $output->writeln('<error>package install error :' . $package_name . ' not found!</error>');
            return false;
        }

        // 是否确认安装
        if (!$is_sure && !$input->hasOption('yes')) {
            // 提示是否确认
            if (!$output->confirm($input, 'Install ' . $package_name . ' package ,sure?', false)) {
                $output->writeln("<info>User cancel.</info>");
                return false;
            }
        }
        $output->writeln("<info>Please waiting...</info>");
        // 备份成功,删除目录及文件
        $packname && FileUtil::unlinkDir(PACKAGE_PATH . $package_name);
        // 执行解包
        try {
            // 开始解包命令处理
            $path = 'public' . self::DS . 'packages';
            if ($input->hasOption('path')) {
                $path .= self::DS . $input->getOption('path');
            }
            $command_params = [$path . self::DS . $zip_name];
            if ($input->hasOption('password')) {
                // 存在解包命令
                $command_params[] = '--password=' . $input->getOption('password');
            }
            // 输出目录
            $outpath          = implode(self::DS, ['src', 'packages', $package_name]);
            $command_params[] = '--outpath=' . $outpath;
            $log              = [];
            Console::call('zip:unpack', $command_params);

            // 当前安装包信息
            $class = 'packages\\' . $package_name . '\\' . ucfirst($package_name);
            if (class_exists($class)) {
                $packageObj = new $class();

                $version = $packageObj->getConfigure('version');
                // 复制目录
                $dirs        = $this->getCopyDir();
                $package_dir = PACKAGE_PATH . $package_name . self::DS;
                foreach ($dirs as $dir => $todir) {
                    if (is_dir($package_dir . $dir)) {
                        FileUtil::copyDir($package_dir . $dir, $todir, true);
                    }
                }

                // 导入安装sql
                PackageDb::executeSqlFile($package_dir . 'install.sql');
                // 执行组件安装自定义方法
                $packageObj->install();

            } else {
                throw new Exception('install error');
            }

            $log[$package_name] = $version;
        } catch (Exception $e) {
            // 删除目录及文件
            FileUtil::unlinkDir(PACKAGE_PATH . $package_name);
            // 恢复备份
            $this->rollbackToPackages($zip_path, $packname, $package_name);
            $output->writeln("<error>Install " . $package_name . " package error,Please try again.Error message:" . $e->getMessage() . "</error>");
            $log = [];
            return false;
        } finally {
            if ($log) {
                // 更新组件安装日志
                $installed_file = PACKAGE_PATH . 'package.lock';
                if (!is_file($installed_file)) {
                    file_put_contents($installed_file, '<?php return []; ?>');
                }
                $default = include $installed_file;
                $log     = array_merge($default, $log);
                file_put_contents($installed_file, "<?php \r\n return " . var_export_min($log, true) . " \r\n ?>");
                $output->writeln("<info>Install package " . $package_name . " success!</info>");
            }
        }

    }

    /**
     * 备份组件
     * @Author   Martinsun<syh@sunyonghong.com>
     * @DateTime 2019-02-26
     * @return   [type]                         [description]
     */
    protected function backupToPackages($output, $savepath, $package_name)
    {
        $output->writeln("<info>Please waiting...</info>");
        // 备份当前组件
        $output->writeln("<info>Backup package: " . $package_name . "...</info>");
        $command_params = [];
        // 包名
        $packname         = $package_name . '.back.' . date('YmdHis') . '.zip';
        $command_params[] = $packname;
        // 打包路径
        $command_params[] = implode(self::DS, ['src', 'packages', $package_name]);
        // 包保存路径
        $command_params[] = '--outpath=' . str_replace(Env::get('root_path'), '', $savepath) . self::DS;
        // 执行解包
        try {
            Console::call('zip:pack', $command_params);
            // 检测是否已经备份
            if (is_file($savepath . self::DS . $packname)) {
                // 删除旧目录
                // $this->remove(PACKAGE_PATH . $package_name);
                return $packname;
            }
        } catch (Exception $e) {
            $output->writeln("<error>Backup error package:" . $package_name . ".Please try again.</error>");
        }
        return false;
    }

    /**
     * 恢复组件
     * @Author   Martinsun<syh@sunyonghong.com>
     * @DateTime 2019-02-26
     * @return   [type]                         [description]
     */
    protected function rollbackToPackages($savepath, $packname, $package_name)
    {
        if ($packname !== false) {
            $command_params   = [];
            $command_params[] = 'public' . self::DS . 'package' . self::DS . $packname;
            $command_params[] = '--outpath=' . implode(self::DS, ['src', 'package', $package_name]);
            Console::call('zip:unpack', $command_params);
            if (is_dir(PACKAGE_PATH . $package_name)) {
                unlink($savepath . self::DS . $packname);
            }
        }
    }

    /**
     * 获取待复制的目录
     * @Author   Martinsun<syh@sunyonghong.com>
     * @DateTime 2019-09-19
     * @return   [type]                         [description]
     */
    public function getCopyDir()
    {
        return [
            'application' => Env::get('app_path'),
            'public'      => Env::get('root_path') . self::DS . 'public',
        ];
    }
}
