<?php

namespace app\common\model;

/**
 * 视频收益规则模型
 */
class VideoRewardRule extends BaseModel
{
    // 表名
    protected $name = 'video_reward_rule';
    
    // 条件类型
    public static $conditionTypeList = [
        'complete' => '看完领取',
        'duration' => '时长领取',
        'count' => '集数领取'
    ];
    
    // 奖励类型
    public static $rewardTypeList = [
        'fixed' => '固定奖励',
        'random' => '随机奖励',
        'progressive' => '递进奖励'
    ];
    
    // 适用范围
    public static $scopeTypeList = [
        'global' => '全局',
        'category' => '分类',
        'video' => '单视频',
        'collection' => '合集'
    ];
    
    // 状态
    public static $statusList = [
        0 => '禁用',
        1 => '启用'
    ];
    
    public function getConditionTypeTextAttr($value, $data)
    {
        return self::$conditionTypeList[$data['condition_type']] ?? '';
    }
    
    public function getRewardTypeTextAttr($value, $data)
    {
        return self::$rewardTypeList[$data['reward_type']] ?? '';
    }
    
    public function getScopeTypeTextAttr($value, $data)
    {
        return self::$scopeTypeList[$data['scope_type']] ?? '';
    }
    
    public function getStatusTextAttr($value, $data)
    {
        return self::$statusList[$data['status']] ?? '';
    }
    
    /**
     * 关联视频分类
     */
    public function category()
    {
        return $this->belongsTo('Category', 'scope_id')->setEagerlyType(0);
    }
    
    /**
     * 关联视频
     */
    public function video()
    {
        return $this->belongsTo('Video', 'scope_id')->setEagerlyType(0);
    }
    
    /**
     * 关联合集
     */
    public function collection()
    {
        return $this->belongsTo('VideoCollection', 'scope_id')->setEagerlyType(0);
    }
    
    /**
     * 获取匹配的规则
     * @param int $userId 用户ID
     * @param array $video 视频信息
     * @return VideoRewardRule|null
     */
    public static function getMatchedRule($userId, $video)
    {
        $user = \app\common\model\User::get($userId);
        $userLevel = $user ? $user->level : 1;
        $registerDays = $user ? floor((time() - $user->createtime) / 86400) : 0;
        
        // 按优先级查询规则
        $query = self::where('status', 1)
            ->where(function ($q) use ($video) {
                $q->where('scope_type', 'global')
                    ->whereOr(function ($q2) use ($video) {
                        $q2->where('scope_type', 'category')
                            ->where('scope_id', $video['category_id'] ?? 0);
                    })
                    ->whereOr(function ($q2) use ($video) {
                        $q2->where('scope_type', 'video')
                            ->where('scope_id', $video['id'] ?? 0);
                    })
                    ->whereOr(function ($q2) use ($video) {
                        $q2->where('scope_type', 'collection')
                            ->where('scope_id', $video['collection_id'] ?? 0);
                    });
            })
            ->where('user_level_min', '<=', $userLevel)
            ->where('user_level_max', '>=', $userLevel)
            ->where(function ($q) {
                $q->whereNull('start_time')->whereOr('start_time', '<=', time());
            })
            ->where(function ($q) {
                $q->whereNull('end_time')->whereOr('end_time', '>=', time());
            });
        
        // 新用户限制
        $query->where(function ($q) use ($registerDays) {
            $q->where('new_user_only', 0)
                ->whereOr(function ($q2) use ($registerDays) {
                    $q2->where('new_user_only', 1)
                        ->where('new_user_days', '>=', $registerDays);
                });
        });
        
        // 按优先级排序
        $rule = $query->order('priority', 'desc')
            ->order('sort', 'asc')
            ->find();
        
        return $rule;
    }
    
    /**
     * 计算奖励金额
     * @return float
     */
    public function calculateReward()
    {
        switch ($this->reward_type) {
            case 'random':
                $min = $this->reward_min ?? 100;
                $max = $this->reward_max ?? 1000;
                return mt_rand($min * 100, $max * 100) / 100;
            case 'progressive':
                // 递进奖励，可根据等级等计算
                return $this->reward_coin;
            default:
                return $this->reward_coin;
        }
    }
}
