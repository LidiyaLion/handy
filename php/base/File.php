<?php
namespace handy\base;

/**
 * 提供常用的文件和路径方法, php本身未提供的.
 *
 * @author Lidiya
 * @since 1.0
 */
class File
{
    /*
     * 仅删除子文件和子目录, 并保留该空目录
     */
    const REMOVE_ONLY_CHILDREN = 0x01;
    /*
     * 递归删除整个目录
     */
    const REMOVE_RECURSE = 0x02;
    /*
     * 递归删除整个目录, 以及符号目录链接的内容
     */
    const REMOVE_SYMLINK_REAL = 0x04;

    /*
     * 存放 mimeType 对应关系
     */
    private static $_mimeTypes = [];

    /**
     * 标准化路径.
     *
     * - "\a/b\c" 标准化为 "/a/b/c"
     * - "/a/b/c/" 标准化为 "/a/b/c"
     * - "/a///b/c" 标准化为 "/a/b/c")
     * - "/a/./b/../c" 标准化为 "/a/c")
     *
     * @param string $path 原始路径
     * @param string $ds 路径分隔符. 默认为 `DIRECTORY_SEPARATOR`.
     * @return string 标准化后的路径
     */
    public static function normalizePath($path, $ds = DIRECTORY_SEPARATOR)
    {
        $path = rtrim(strtr($path, '/\\', $ds . $ds), $ds);
        if (strpos($ds . $path, "{$ds}.") === false && strpos($path, "{$ds}{$ds}") === false) {
            return $path;
        }

        $parts = [];
        foreach (explode($ds, $path) as $part) {
            if ($part === '..' && !empty($parts) && end($parts) !== '..') {
                array_pop($parts); // 遇 '..' 退到上层目录
            } elseif ($part === '.' || $part === '' && !empty($parts)) {
                continue;
            } else {
                $parts[] = $part;
            }
        }
        $path = implode($ds, $parts);
        return $path === '' ? '.' : $path;
    }

    /**
     * 检测文件的 MIME type.
     *
     * @param string $file 文件名称.
     * @param string $magicFile 指定的 magic.mime 文件.
     * @param boolean $checkExtension 如果不支持 fileinfo, 是否根据扩展名返回类型.
     * @return null|string  MimeType (text/plain).
     */
    public static function getMimeType($file, $magicFile = null, $checkExtension = true)
    {
        if (!extension_loaded('fileinfo')) {
            if ($checkExtension) {
                return static::getMimeTypeByExtension($file, $magicFile);
            } else {
                return null;
            }
        }

        $info = finfo_open(FILEINFO_MIME_TYPE, $magicFile);

        if ($info) {
            $result = finfo_file($info, $file);
            finfo_close($info);

            if ($result !== false) {
                return $result;
            }
        }

        return $checkExtension ? static::getMimeTypeByExtension($file, $magicFile) : null;
    }

    /**
     * 根据扩展名检测文件的 MIME type.
     *
     * @param string $file 文件名
     * @param string $magicFile 指定的 magic.mime 文件, 不指定则返回扩展名.
     * @return null|string MIME type 或扩展名.
     */
    public static function getMimeTypeByExtension($file, $magicFile = null)
    {
        $mimeTypes = static::loadMimeTypes($magicFile);

        if (($ext = pathinfo($file, PATHINFO_EXTENSION)) !== '') {
            $ext = strtolower($ext);
            if (isset($mimeTypes[$ext])) {
                return $mimeTypes[$ext];
            } else {
                return $ext;
            }
        }

        return null;
    }

    /**
     * 加载 magic.mime 文件.
     * @param string $magicFile 指定的 magic.mime 文件.
     * @return array mime type 关联数组.
     */
    protected static function loadMimeTypes($magicFile)
    {
        if (empty($magicFile)) {
            return null;
        }
        if (!isset(self::$_mimeTypes[$magicFile])) {
            self::$_mimeTypes[$magicFile] = require($magicFile);
        }
        return self::$_mimeTypes[$magicFile];
    }

    /**
     * 拷贝目录, 级联拷贝.
     *
     * @param string $src 源路径
     * @param string $dst 目标路径
     * @param integer $mode 目标路径权限, 默认为 0775.
     * @return boolean|string true为成功，否则为错误信息.
     */
    public static function copyDirectory($src, $dst, $mode = 0775)
    {
        if (!is_dir($dst)) {
            static::createDirectory($dst, $mode, true);
        }

        $handle = opendir($src);
        if ($handle === false) {
            return "Unable to open directory: $src";
        }

        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $from = $src . DIRECTORY_SEPARATOR . $file;
            $to = $dst . DIRECTORY_SEPARATOR . $file;

            if (is_file($from)) {
                copy($from, $to);
                @chmod($to, $mode);
            } else {
                // 子路径拷贝错误, 立即返回
                if ($ret = static::copyDirectory($from, $to, $mode) !== true) {
                    closedir($handle);
                    return $ret;
                }
            }
        }
        closedir($handle);

        return true;
    }

    /**
     * 新建目录.
     *
     * @param string $path 路径.
     * @param integer $mode 权限设置.
     * @param boolean $recursive 是否级联创建.
     * @return boolean|string true为成功，否则为错误信息.
     */
    public static function createDirectory($path, $mode = 0775, $recursive = true)
    {
        // 已存在即返回，避免 mkdir 报错
        if (is_dir($path)) {
            return true;
        }

        try {
            $result = mkdir($path, $mode, $recursive);
            chmod($path, $mode); // 避免 umask 的设置影响权限赋予
        } catch(\Exception $e) {
            return "Create dir [$path] failed!" . PHP_EOL . $e->getMessage();
        }

        return $result;
    }

    /**
     * 递归删除整个目录
     *
     * @param string $dir 目录
     * @param integer $mode 删除模式，默认为 REMOVE_RECURSE
     * @return boolean|string true为成功，否则为错误信息
     */
    public static function removeDirectory($dir, $mode = self::REMOVE_RECURSE)
    {
        if (!is_dir($dir)) {
            return true;
        }

        if ((self::REMOVE_SYMLINK_REAL & $mode) || !is_link($dir)) {
            if (!($handle = opendir($dir))) {
                return 'opendir [' . $dir . '] failed.';
            }

            while (($file = readdir($handle)) !== false) {
                if ($file === '.' || $file === '..') {
                    continue;
                }

                $path = $dir . DIRECTORY_SEPARATOR . $file;
                if (is_dir($path)) {
                    if (($ret = static::removeDirectory($path, ($mode & 0xFD))) !== true) {
                        return $ret;
                    }
                } else {
                    unlink($path);
                }
            }
            closedir($handle);
        }

        if (is_link($dir)) {
            unlink($dir);
        } else {
            (self::REMOVE_ONLY_CHILDREN & $mode) || rmdir($dir);
        }

        return true;
    }

    /**
     * 目录是否为空
     *
     * @param string $dir 目录
     * @return boolean|string true 空, false 非空, other 错误信息.
     */
    public function isEmpty($dir)
    {
        if (!($handle = opendir($dir))) {
            return 'opendir [' . $dir . '] failed.';
        }

        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            closedir($handle);
            return false;
        }

        closedir($handle);
        return true;
    }
}

