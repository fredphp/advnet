<?php

namespace app\common\model;

use think\Model;
use traits\model\SoftDelete;

/**
 * 基础模型类
 * 支持软删除功能
 */
class BaseModel extends Model
{
    // 使用软删除
    use SoftDelete;
    
    // 删除时间字段
    protected $deleteTime = 'deletetime';
    
    // 默认软删除值
    protected $defaultSoftDelete = null;
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    /**
     * 获取可用状态列表
     */
    public static function getStatusList()
    {
        return [
            0 => '禁用',
            1 => '启用'
        ];
    }
    
    /**
     * 获取状态文本
     */
    public function getStatusTextAttr($value, $data)
    {
        $list = self::getStatusList();
        return $list[$data['status']] ?? '';
    }
}
