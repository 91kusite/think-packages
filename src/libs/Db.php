<?php
namespace kusite\package\libs;

use think\Db as ThinkDb;
use think\facade\Config;

class Db
{
    const DS = DIRECTORY_SEPARATOR;
    /**
     * 导入SQL
     *
     * @param   string $name 插件名称
     * @return  boolean
     */
    public static function executeSqlFile($sqlfile = '')
    {
        if (!is_file($sqlfile)) {
            $sqlfile = PACKAGE_PATH . $sqlfile . self::DS . 'install.sql';
        }
        if (is_file($sqlfile)) {
            $lines    = file($sqlfile);
            $templine = '';
            $prefix = Config::get('database.prefix');
            foreach ($lines as $line) {
                if (substr($line, 0, 2) == '--' || $line == '' || substr($line, 0, 2) == '/*') {
                    continue;
                }

                $templine .= $line;
                if (substr(trim($line), -1, 1) == ';') {
                    $templine = str_ireplace('__PREFIX__', $prefix, $templine);
                    $templine = str_ireplace('INSERT INTO ', 'INSERT IGNORE INTO ', $templine);
                    try {
                        ThinkDb::execute($templine);
                    } catch (\PDOException $e) {
                        //$e->getMessage();
                    }
                    $templine = '';
                }
            }
        }
        return true;
    }
}
