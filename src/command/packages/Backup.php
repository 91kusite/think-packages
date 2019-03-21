<?php
namespace think\command\packages;

use think\Console;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\facade\Env;

class Backup extends Command
{
    const DS = DIRECTORY_SEPARATOR;
    /**
     * 插件包备份
     * @Author   MartinSun<syh@sunyonghong.com>
     * @DateTime 2019-02-26
     * @return   [type]                         [description]
     */
    protected function configure()
    {
        $this->setName('package:backup')
            ->addArgument('name', Argument::REQUIRED, 'set package\'s name to backup.', null)
            ->addOption('password', 'p', Option::VALUE_OPTIONAL, 'set package\'s password.', null)
            ->setDescription('Backup package.');
    }
    protected function execute(Input $input, Output $output)
    {
        // 插件名称
        $package_name = trim($input->getArgument('name'));

        if (!$package_name) {
            $output->writeln('<error>Please set package\'s name.</error>');
            return false;
        }
        // 查找插件是否已经安装
        $package_path = PACKAGE_PATH.$package_name;
        if (!is_dir($package_path)) {
            $output->writeln('<error>Backup package error:' . $package_name . ' not found!</error>');
            return false;
        }
        $output->writeln("<info>Please waiting...</info>");
        // 开始打包命令处理
        $command_params = [];
        // 包名
        $command_params[] = $package_name . '.back.' . date('YmdHis') . '.zip';
        // 打包路径
        $command_params[] = implode(self::DS, ['src', 'packages', $package_name]);
        if ($input->hasOption('password')) {
            // 设置读取密码
            $command_params[] = '--password=' . $input->getOption('password');
        }
        // 包保存路径
        $outpath          = 'public' . self::DS . 'packages' . self::DS;
        $command_params[] = '--outpath=' . $outpath;
        // 执行解包
        try {
            Console::call('zip:pack', $command_params);
        } catch (\Exception $e) {
            $output->writeln("<error>Backup error package:" . $package_name . ".Please try again.</error>");
            return false;
        }
        $output->writeln("<info>Backup package " . $package_name . " success!</info>");

    }

}
