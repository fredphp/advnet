<?php

namespace app\common\model;

/**
 * 视频合集模型
 */
class VideoCollection extends BaseModel
{
    // 表名
    protected $name = 'video_collection';
    
    // 追加属性
    protected $append = [
        'status_text',
        'reward_type_text'
    ];
    
    // 状态列表
    public static $statusList = [
        0 => '禁用',
        1 => '启用'
    ];
    
    // 奖励类型
    public static $rewardTypeList = [
        'per_video' => '每集奖励',
        'complete' => '看完合集奖励',
        'progressive' => '递进奖励'
    ];
    
    public function getStatusTextAttr($value, $data)
    {
        return self::$statusList[$data['status']] ?? '';
    }
    
    public function getRewardTypeTextAttr($value, $data)
    {
        return self::$rewardTypeList[$data['reward_type']] ?? '';
    }
    
    /**
     * 关联视频
     */
    public function videos()
    {
        return $this->belongsToMany('Video', 'video_collection_item', 'video_id', 'collection_id')
            ->withField('id,title,duration,cover_url,video_url')
            ->order('episode', 'asc');
    }
    
    /**
     * 关联合集视频项
     */
    public function items()
    {
        return $this->hasMany('VideoCollectionItem', 'collection_id');
    }
    
    /**
     * 获取合集视频列表
     * @param int $collectionId
     * @return array
     */
    public static function getVideoList($collectionId)
    {
        $items = VideoCollectionItem::where('collection_id', $collectionId)
            ->order('episode', 'asc')
            ->column('video_id,episode', 'video_id');
        
        return $items;
    }
    
    /**
     * 获取合集总集数
     * @return int
     */
    public function getTotalEpisodes()
    {
        return VideoCollectionItem::where('collection_id', $this->id)->count();
    }
    
    /**
     * 更新统计信息
     */
    public function updateStats()
    {
        $stats = VideoCollectionItem::where('collection_id', $this->id)
            ->field('COUNT(*) as count')
            ->find();
        
        $this->video_count = $stats['count'] ?? 0;
        $this->save();
    }
}
