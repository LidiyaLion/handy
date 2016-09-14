<?php
namespace handy\base;

/**
 * �ṩ���õ������������, php����δ�ṩ��.
 *
 * @author Lidiya
 * @since 1.0
 */
class ArraySuite
{
    /**
     * ɾ������ָ��λ�õ�Ԫ��.
     * @param array $input ����.
     * @param int $offset ƫ��λ��.
     * @return array ɾ����Ԫ��.
     */
    public static function remove(array &$input, $offset)
    {
        return array_splice($input, $offset, 1);
    }

    /**
     * ɾ������ָ��λ�õ�Ԫ��.
     *
     * - ����Ԫ�صļ����������ֻ�;
     * - ����Ԫ��λ��֮����������ֻ�����,�����°���ֵ.
     *
     * @param array $input ����.
     * @param int $offset ƫ��λ��.
     * @param mixed $replacement ɾ����Ԫ��.
     */
    public static function insert(array &$input, $offset, $replacement)
    {
        array_splice($input, $offset, 0, $replacement);
    }

    /**
     * ��������������Ԫ�ص�λ��.
     *
     * - ����������Ԫ�صļ����������ֻ�;
     * - ����Ԫ��λ��֮����������ֻ�����,�����°���ֵ.
     *
     * @param array $input ����.
     * @param int $posLeft ����Ԫ��λ��.
     * @param int $posRight ����Ԫ��λ��.
     */
    public static function swap(array &$input, $posLeft, $posRight)
    {
        $left = array_slice($input, $posLeft, 1);
        $right = array_slice($input, $posRight, 1);
        array_splice($input, $posLeft, 1, $right);
        array_splice($input, $posRight, 1, $left);
    }
}


