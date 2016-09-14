<?php
namespace handy\base;

/**
 * 提供常用的数组操作方法, php本身未提供的.
 *
 * @author Lidiya
 * @since 1.0
 */
class ArraySuite
{
    /**
     * 删除数组指定位置的元素.
     * @param array $input 数组.
     * @param int $offset 偏移位置.
     * @return array 删除的元素.
     */
    public static function remove(array &$input, $offset)
    {
        return array_splice($input, $offset, 1);
    }

    /**
     * 删除数组指定位置的元素.
     *
     * - 插入元素的键名将会数字化;
     * - 插入元素位置之后的所有数字化键名,会重新按序赋值.
     *
     * @param array $input 数组.
     * @param int $offset 偏移位置.
     * @param mixed $replacement 删除的元素.
     */
    public static function insert(array &$input, $offset, $replacement)
    {
        array_splice($input, $offset, 0, $replacement);
    }

    /**
     * 交换数组中两个元素的位置.
     *
     * - 交换的两个元素的键名将会数字化;
     * - 交换元素位置之后的所有数字化键名,会重新按序赋值.
     *
     * @param array $input 数组.
     * @param int $posLeft 交换元素位置.
     * @param int $posRight 交换元素位置.
     */
    public static function swap(array &$input, $posLeft, $posRight)
    {
        $left = array_slice($input, $posLeft, 1);
        $right = array_slice($input, $posRight, 1);
        array_splice($input, $posLeft, 1, $right);
        array_splice($input, $posRight, 1, $left);
    }
}


