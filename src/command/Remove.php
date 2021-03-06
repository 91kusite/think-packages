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
use think\facade\Cache;
use think\facade\Env;

class Remove extends Command
{
    const DS = DIRECTORY_SEPARATOR;
    /**
     * 移除组件包
     * @Author   MartinSun<syh@sunyonghong.com>
     * @DateTime 2019-02-26
     * @return   [type]                         [description]
     */
    protected function configure()
    {
        $this->setName('package:remove')
            ->addArgument('name', Argument::REQUIRED, 'set package\'s name to uninstall.', null)
            ->addOption('yes', 'y', Option::VALUE_NONE, 'make sure.', null)
            ->addOption('path', null, Option::VALUE_OPTIONAL, 'set package\'s path.', null)
            ->setDescription('Uninstall package.');
    }
    protected function execute(Input $input, Output $output)
    {
        // 组件名称
        $package_name = trim($input->getArgument('name'));
        // 查找组件是否已经安装
        $package_path = PACKAGE_PATH . $package_name;
        if (!is_dir($package_path)) {
            $output->writeln('<error>Uninstall package error:' . $package_name . ' not found!</error>');
            return false;
        }
        // 是否确认卸载
        if (!$input->hasOption('yes')) {
            // 提示是否确认
            if (!$output->confirm($input, 'Are you sure remove ' . $package_name . '\'s package?', false)) {
                $output->writeln("<info>User cancel.</info>");
                return false;
            }
        }

        // 当前安装包信息
        $class = 'packages\\' . $package_name . '\\' . ucfirst($package_name);
        if (class_exists($class)) {
            $packageObj = new $class();
            // 如果为系统组件,则不支持卸载
            if ($packageObj->getConfigure('is_system') == 1) {
                $output->writeln("<error>This is a system package and does not support uninstallation.</error>");
                return false;
            }
            $version  = $packageObj->getConfigure('version');
            $zip_path = Env::get('root_path') . 'public' . self::DS . 'packages';
            if ($input->hasOption('path')) {
                $zip_path .= self::DS . $input->getOption('path');
            }
            // 检测安装包是否存在
            if (!file_exists($zip_path . self::DS . $package_name . '.' . $version . '.zip')) {
                // 提示是否备份
                if ($output->confirm($input, $package_name . '\'s package did not find a backup.Do you need backups?', false)) {
                    // 调用备份命令
                    $packname = $this->backupToPackages($output, $zip_path, $package_name, $version);
                    if ($packname === false) {
                        return false;
                    }
                }
            }
        }
        try {
            $packageObj = new $class();
            // 执行卸载sql
            PackageDb::executeSqlFile($package_path . self::DS . 'uninstall.sql');
            // 执行组件卸载
            $packageObj->uninstall();
            $packagename = $packageObj->getPackageName();
            $files       = $this->getCopyFiles($this->getCopyDir($package_path));
            $this->removeCopyFiles($files);
            FileUtil::unlinkDir($package_path);
            // 移除静态资源目录
            // $out_static_dir = implode(self::DS, [Env::get('root_path'), 'public', 'static', 'packages', $package_name]);
            // FileUtil::unlinkDir($out_static_dir);
            // 更新组件安装日志
            $log                = [];
            $log[$package_name] = '';
        } catch (\Exception $e) {
            $output->writeln("<error>Uninstall package error:" . $package_name . ".Please try again.</error>");
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
                $log     = array_diff_key($default, $log);
                file_put_contents($installed_file, "<?php \r\n return " . var_export_min($log, true) . " \r\n ?>");
                Cache::rm('package_' . $packagename);
            }
        }
        $output->writeln("<info>Remove package " . $package_name . " success!</info>");

    }

    /**
     * 执行删除目录和文件的方法
     * @Author   MartinSun<syh@sunyonghong.com>
     * @DateTime 2019-02-26
     * @param    string     $path 需要删除的目录或文件
     * @return
     */
    protected function removeCopyFiles($files)
    {
        foreach ($files as $file) {
            FileUtil::unlinkFile($file);
            $this->removeEmptyDir(dirname($file));
        }
    }

    protected function removeEmptyDir($dir)
    {
        // 只包含.和..,判断为空
        if (is_dir($dir) && count(scandir($dir)) == 2) {
            FileUtil::unlinkDir($dir);
            $this->removeEmptyDir(dirname($dir));
        }
    }

    /**
     * 备份组件
     * @Author   Martinsun<syh@sunyonghong.com>
     * @DateTime 2019-02-26
     * @return   [type]                         [description]
     */
    protected function backupToPackages($output, $savepath, $package_name, $version)
    {
        $output->writeln("<info>Please waiting...</info>");
        // 备份当前组件
        $output->writeln("<info>Backup package: " . $package_name . "...</info>");
        $command_params = [];
        // 包名
        $packname         = $package_name . '.' . $version . '.zip';
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
                return $packname;
            }
        } catch (Exception $e) {
            $output->writeln("<error>Backup error package:" . $package_name . ".Please try again.</error>");
        }
        return false;
    }

    public function getCopyFiles($package_dir)
    {
        static $files;
        foreach ($package_dir as $path => $topath) {
            if (!is_dir($path)) {
                continue;
            }
            // 打开目录
            $dh = opendir($path);
            // 循环读取目录
            while (($file = readdir($dh)) !== false) {

                // 过滤掉当前目录'.'和上一级目录'..'
                if ($file == '.' || $file == '..') {
                    continue;
                }

                // 如果该文件是一个目录，则进入递归
                if (is_dir($path . self::DS . $file)) {
                    $this->getCopyFiles([$path . self::DS . $file => $topath . $file . self::DS]);
                } else {
                    // 如果不是一个目录，则将其删除
                    $files[$path . self::DS . $file] = $topath . $file;
                }
            }
            // 退出循环后(此时已经删除所有了文件)，关闭目录并删除
            closedir($dh);
        }

        return $files;
    }

    /**
     * 获取待复制的目录
     * @Author   Martinsun<syh@sunyonghong.com>
     * @DateTime 2019-09-19
     * @return   [type]                         [description]
     */
    public function getCopyDir($package_dir)
    {
        return [
            $package_dir . self::DS . 'application' => Env::get('app_path'),
            $package_dir . self::DS . 'public'      => Env::get('root_path') . self::DS . 'public' . self::DS,
        ];
    }

}
