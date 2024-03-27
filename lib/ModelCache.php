<?php
declare(strict_types=1);

namespace Fengdangxing\ModelCache;

use Fengdangxing\HyperfRedis\RedisHelper;
use Hyperf\Database\Model\Builder;
use Hyperf\DbConnection\Model\Model;
use Hyperf\Utils\Codec\Json;


class ModelCache extends Model
{
    /**
     * @Notes:插入一行
     * @param array $data
     * @return int
     */
    public static function insertOneGetId(array $data): int
    {
        $result = self::insertGetId($data);
        self::delRedis(true);
        return $result;
    }

    /**
     * @Notes:插入多行
     * @param array $data | [
     * ['email' => 'taylor@example.com', 'votes' => 0],
     * ['email' => 'dayle@example.com', 'votes' => 0]
     * ]
     * @return bool
     */
    public static function insertMore(array $data): bool
    {
        $result = self::insert($data);
        self::delRedis(true);
        return $result;
    }

    /**
     * @Notes:插入或增加
     * @param array $where
     * @param array $data
     * @return bool
     */
    public static function updateOrInsertData(array $where, array $data): bool
    {

        $info = self::query()->where($where)->first();
        if (empty($info)) {
            $result = self::insert($data);
        } else {
            $result = (bool)self::where($where)->update($data);
        }
        self::delRedis(true);
        return $result;
    }

    /**
     * @Notes: 修改信息
     * @param array $where
     * @param array $update
     * @return int
     */
    public static function updateInfo(array $where = [], array $update = []): int
    {
        $result = self::where($where)->update($update);
        self::delRedis(true);
        return $result;
    }

    /**
     * @Notes: 修改信息
     * @param Builder $builder
     * @param array $update
     * @return int
     */
    public static function updateBuilderInfo(Builder $builder, array $update = []): int
    {

        $result = $builder->update($update);
        self::delRedis(true);
        return $result;
    }

    /**
     * @Notes: 获取一行数据
     * @param array $where
     * @param string[] $field
     * @param bool $cache
     * @return array
     */
    public static function getRow(array $where = [], array $field = ['*'], bool $cache = true): array
    {
        $params['showFields'] = $field;
        $builder = self::where($where);
        return self::getRedis(static::$redisInfo, __FUNCTION__, $params, $where, $cache, $builder);
    }

    /**
     * @Notes: 获取一行数据
     * @param Builder $builder
     * @param array $requestParams
     * @param string[] $field
     * @param bool $cache
     * @return array
     */
    public static function getBuilderRow(Builder $builder, array $requestParams, array $field = ['*'], bool $cache = true): array
    {
        $params['showFields'] = $field;
        return self::getRedis(static::$redisInfo, __FUNCTION__, $params, $requestParams, $cache, $builder);
    }

    /**
     * @Notes: 获取总数
     * @param array $where
     * @param string[] $field
     * @param bool $cache
     * @return int
     */
    public static function getCount(array $where = [], array $field = ['*'], bool $cache = true): int
    {
        $params['showFields'] = $field;
        $builder = self::where($where);
        return self::getRedis(static::$redisInfo, __FUNCTION__, $params, $where, $cache, $builder);
    }

    /**
     * @Notes: 获取总数
     * @param Builder $builder
     * @param array $where
     * @param string[] $field
     * @param bool $cache
     * @return int
     */
    public static function getBuilderCount(Builder $builder, array $where = [], array $field = ['*'], bool $cache = true): int
    {
        $params['showFields'] = $field;
        return self::getRedis(static::$redisInfo, __FUNCTION__, $params, $where, $cache, $builder);
    }

    /**
     * @Notes:获取单个字段
     * @param Builder $builder
     * @param array $requestParams
     * @param array $field
     * @param bool $cache
     * @return array|mixed|mixed[]
     */
    public static function getPluck(Builder $builder, array $requestParams, array $field = ['*'], bool $cache = true)
    {
        $params['showFields'] = $field;
        return self::getRedis(static::$redisList, __FUNCTION__, $params, $requestParams, $cache, $builder);
    }

    /**
     * @Notes:列表
     * @param Builder $builder
     * @param array $requestParams
     * @param array $showFields
     * @param int $page
     * @param int $limit
     * @return array
     */
    public static function getPageList(Builder $builder, array $requestParams, array $showFields = ['*'], int $page = 0, int $limit = 10)
    {
        $params['showFields'] = $showFields;
        $params['page'] = $page;
        $params['limit'] = $limit;
        return self::getRedis(static::$redisList, __FUNCTION__, $params, $requestParams, true, $builder);
    }

    /**
     * @Notes: 获取所有数据
     * @param array $where
     * @param array|string[] $showFields
     * @param bool $cache
     * @return array
     */
    public static function getAllList(array $where = [], array $showFields = ['*'], bool $cache = true)
    {
        $params['showFields'] = $showFields;
        $builder = self::where($where);
        return self::getRedis(static::$redisList, __FUNCTION__, $params, $where, $cache, $builder);
    }

    /**
     * @Notes: 获取所有数据-构造器
     * @param Builder $builder
     * @param array $requestParams
     * @param array|string[] $showFields
     * @return array
     */
    public static function getBuilderAllList(Builder $builder, array $requestParams, array $showFields = ['*']): array
    {
        $params['showFields'] = $showFields;
        return self::getRedis(static::$redisList, __FUNCTION__, $params, $requestParams, true, $builder);
    }

    /**
     * @Notes: 根据条件获取指定字段值
     * @param array $requestParams
     * @param array $showFields
     * @return string
     */
    public static function getValue(array $requestParams = [], array $showFields = ['*'])
    {
        $params['showFields'] = $showFields;
        $builder = self::where($requestParams);
        $requestParams[] = ['diffOperationType', 'is', 'getValue'];//加入这样代码区分，防止跟getRow产生一样的hkey导致报错
        return self::getRedis(static::$redisInfo, __FUNCTION__, $params, $requestParams, true, $builder);
    }

    /**
     * @Notes: 删除-单条
     * @param int $id
     * @return int|mixed
     */
    public static function softDeleteOne(int $id)
    {
        $result = self::query()->where('id', '=', $id)->delete();
        self::delRedis(true);
        return $result;
    }

    /**
     * @Notes: 删除-根据条件删除
     * @param array $where
     * @return int|mixed
     */
    public static function softDeleteMore(array $where)
    {
        $result = self::query()->where($where)->delete();
        self::delRedis(true);
        return $result;
    }

    /**
     * @Notes: 物理删除-根据条件
     * @param array $where
     * @return int
     */
    public static function forceDeleteMore(array $where): int
    {
        $result = self::query()->where($where)->forceDelete();
        self::delRedis(true);
        return $result;
    }

    public static function delRedis($isTrue = false)
    {
        RedisHelper::init()->del(static::$redisList);
        RedisHelper::init()->del(static::$redisInfo);
        if ($isTrue) {
            call_user_func("static::hasDelRedis");
        }
    }

    /**
     * @Notes: 根据模型列表删除对应缓存， 用于事务后删除缓存避免错误
     * @param $models //删除缓存 [PublishBatchDetailModel::class, PublishPageRecord::class, PublishBatchModel::class,PublishAuditModel::class]);
     * @return void
     */
    public static function delRedisCacheByTransaction($models)
    {
        foreach ($models as $model) {
            $model::delRedis(true);
        }
    }

    private static function getRedis(string $key, string $mod, array $params, array $requestParams, $cache, Builder $builder)
    {
        $ret = [];
        $showFields = $params['showFields'];
        $limit = $params['limit'] ?? 10;
        $page = $params['page'] ?? 1;

        $hKey = md5($builder->toSql() . Json::encode($requestParams) . Json::encode($params) . $mod);
        if ($cache && ($retJson = RedisHelper::init()->hGet($key, $hKey)) !== false) {
            $ret = $mod == "getValue" ? $retJson : Json::decode((string)$retJson, true);
        } else {
            switch ($mod) {
                case 'getBuilderAllList':
                case 'getAllList':
                    $ret = $builder->get($showFields)->toArray();
                    break;
                case 'getPageList':
                    $ret = $builder->paginate($limit, $showFields, 'page', $page);
                    $ret = Json::encode($ret);
                    $ret = Json::decode((string)$ret, true);
                    break;
                case 'getRow':
                case 'getBuilderRow':
                    $ret = $builder->select($showFields)->first();
                    $ret = $ret ? $ret->toArray() : [];
                    break;
                case 'getPluck':
                    $ret = $builder->pluck($showFields[0]);
                    $ret = $ret ? $ret->toArray() : [];
                    break;
                case 'getValue':
                    $ret = $builder->value($showFields[0]);
                    break;
                case 'getCount':
                case 'getBuilderCount':
                    $ret = $builder->count();
                    break;
                default:
                    break;

            }
            RedisHelper::init()->hSet($key, $hKey, is_array($ret) ? Json::encode($ret) : $ret);
            RedisHelper::init()->expire($key, RedisHelper::$timeout);
        }
        return $ret;
    }
}
