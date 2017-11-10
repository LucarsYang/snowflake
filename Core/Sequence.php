<?php

namespace Core;

/**
 * Class Sequence
 *
 * @package Core
 */
class Sequence
{

    /**
     * SYSTEM CATEGORY
     */
    const DEFAULT = 00000;
    const MEMBER  = 10001;
    const USER    = 10002;

    /**
     * Generate Sequence by SnowFlake
     * 取得系統產生的流水號(SnowFlake)
     *
     * @param int $categoryId 序號的類別 ID
     *
     * @return string|null 系統產生的流水號
     * @throws \RuntimeException
     *
     * @author Lucars <lucars@gmail.com>
     */
    public static function generate(int $categoryId) : ?string
    {
        try
        {
            // UTC + 0
            ini_set( 'date.timezone', 'UTC' );

            // 防止單一進程多次要求同類ID, 產生碰撞的問題
            usleep(1);

            // 主機名稱 ex: hosts1000001;
            $hostName = gethostname();

            // 取得 pid
            $pid = getmypid();

            // 時間戳記
            $timestamp = microtime();

            // 缺少分類 Id
            if ( ! $categoryId)
            {
                throw new \RuntimeException('CID Error.');
            }

            // 缺少系統 Id
            if ( ! $hostName)
            {
                throw new \RuntimeException('HN Error.');
            }

            // 微秒(10-6)
            $microSecond = substr($timestamp, 2, 6);

            // 秒
            $second = substr($timestamp, 11);

            // 日期轉 32 進位
            $currentTime = self::encrypt(date('YmdHis', $second));

            // 主機 Id
            $machineId = substr($hostName, 5, 7);

            // mixId = 主機 Id + pid 轉 32 進位
            $mixId = self::encrypt($machineId . str_pad($pid, 7, '0', STR_PAD_LEFT));

            // $sequence = $category(5) + $currentTime(9) + $mixId(10) + $microSecond(6) = total(30)
            $sequence = str_pad((string) $categoryId, 5, '0', STR_PAD_LEFT)
                . $currentTime
                . str_pad($mixId, 10, '0', STR_PAD_LEFT)
                . $microSecond;

            return $sequence;
        }
        catch (\RuntimeException $e)
        {
            if (DEBUG)
            {
                echo $e->getMessage();
            }
            return null;
        }
    }

    /**
     * parse
     *
     * @param string $sequence
     *
     * @return array
     */
    public static function parse(string $sequence) : array
    {
        if (strlen($sequence) == 30)
        {
            return array(
                'category' => (int) substr($sequence, 0, 5),
                'currentTime' => substr($sequence, 5, 9)
            );
        }

        return array(
            'category' => 0,
            'currentTime' => ''
        );
    }

    /**
     * Encrypt Number
     * 流水號的數字加密
     *
     * @param string $input 要加密的數字
     *
     * @return string 加密後的字串
     * @throws \RuntimeException
     *
     * @author Lucars <lucars@gmail.com>
     */
    private static function encrypt(string $input = '') : string
    {
        // 要加密的流水號不可為空值
        if ( ! $input)
        {
            throw new \RuntimeException('Missing input');
        }
        return base_convert($input, 10, 32);
    }

    /**
     * @param string $sequence
     * @param string $category
     * @return bool
     */
    public static function validCategory(string $sequence, string $category) : bool
    {
        // search $category in place 0 from $id
        return (strpos($sequence, $category) === 0);
    }

    /**
     * @param string $nodeId
     * @return bool|string
     */
    public static function getDateFormNodeId(string $nodeId)
    {
        return substr(base_convert(substr($nodeId, 5, 9), 32, 10), 0, 8);
    }
}