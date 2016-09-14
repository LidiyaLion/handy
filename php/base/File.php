<?php
namespace handy\base;

/**
 * �ṩ���õ��ļ���·������, php����δ�ṩ��.
 *
 * @author Lidiya
 * @since 1.0
 */
class File
{
    /*
     * ��ɾ�����ļ�����Ŀ¼, �������ÿ�Ŀ¼
     */
    const REMOVE_ONLY_CHILDREN = 0x01;
    /*
     * �ݹ�ɾ������Ŀ¼
     */
    const REMOVE_RECURSE = 0x02;
    /*
     * �ݹ�ɾ������Ŀ¼, �Լ�����Ŀ¼���ӵ�����
     */
    const REMOVE_SYMLINK_REAL = 0x04;

    /*
     * ��� mimeType ��Ӧ��ϵ
     */
    private static $_mimeTypes = [];

    /**
     * ��׼��·��.
     *
     * - "\a/b\c" ��׼��Ϊ "/a/b/c"
     * - "/a/b/c/" ��׼��Ϊ "/a/b/c"
     * - "/a///b/c" ��׼��Ϊ "/a/b/c")
     * - "/a/./b/../c" ��׼��Ϊ "/a/c")
     *
     * @param string $path ԭʼ·��
     * @param string $ds ·���ָ���. Ĭ��Ϊ `DIRECTORY_SEPARATOR`.
     * @return string ��׼�����·��
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
                array_pop($parts); // �� '..' �˵��ϲ�Ŀ¼
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
     * ����ļ��� MIME type.
     *
     * @param string $file �ļ�����.
     * @param string $magicFile ָ���� magic.mime �ļ�.
     * @param boolean $checkExtension �����֧�� fileinfo, �Ƿ������չ����������.
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
     * ������չ������ļ��� MIME type.
     *
     * @param string $file �ļ���
     * @param string $magicFile ָ���� magic.mime �ļ�, ��ָ���򷵻���չ��.
     * @return null|string MIME type ����չ��.
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
     * ���� magic.mime �ļ�.
     * @param string $magicFile ָ���� magic.mime �ļ�.
     * @return array mime type ��������.
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
     * ����Ŀ¼, ��������.
     *
     * @param string $src Դ·��
     * @param string $dst Ŀ��·��
     * @param integer $mode Ŀ��·��Ȩ��, Ĭ��Ϊ 0775.
     * @return boolean|string trueΪ�ɹ�������Ϊ������Ϣ.
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
                // ��·����������, ��������
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
     * �½�Ŀ¼.
     *
     * @param string $path ·��.
     * @param integer $mode Ȩ������.
     * @param boolean $recursive �Ƿ�������.
     * @return boolean|string trueΪ�ɹ�������Ϊ������Ϣ.
     */
    public static function createDirectory($path, $mode = 0775, $recursive = true)
    {
        // �Ѵ��ڼ����أ����� mkdir ����
        if (is_dir($path)) {
            return true;
        }

        try {
            $result = mkdir($path, $mode, $recursive);
            chmod($path, $mode); // ���� umask ������Ӱ��Ȩ�޸���
        } catch(\Exception $e) {
            return "Create dir [$path] failed!" . PHP_EOL . $e->getMessage();
        }

        return $result;
    }

    /**
     * �ݹ�ɾ������Ŀ¼
     *
     * @param string $dir Ŀ¼
     * @param integer $mode ɾ��ģʽ��Ĭ��Ϊ REMOVE_RECURSE
     * @return boolean|string trueΪ�ɹ�������Ϊ������Ϣ
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
     * Ŀ¼�Ƿ�Ϊ��
     *
     * @param string $dir Ŀ¼
     * @return boolean|string true ��, false �ǿ�, other ������Ϣ.
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

