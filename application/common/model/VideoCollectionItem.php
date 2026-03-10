<?php

namespace app\common\model;

use think\Model;

/**
 * 合集视频关联模型
 */
class VideoCollectionItem extends Model
{
    // 表名
    protected $name = 'video_collection_item';
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = false;
    
    /**
     * 关联合集
     */
    public function collection()
    {
        return $this->belongsTo('VideoCollection', 'collection_id');
    }
    
    /**
     * 关联视频
     */
    public function video()
    {
        return $this->belongsTo('Video', 'video_id');
    }
}
