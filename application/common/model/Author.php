<?php

namespace app\common\model;

/**
 * 发布者/作者模型
 */
class Author extends BaseModel
{
    // 表名
    protected $name = 'author';
    
    // 追加属性
    protected $append = [
        'status_text',
        'verify_status_text',
        'avatar_text'
    ];
    
    // 状态列表
    public static $statusList = [
        'normal' => '正常',
        'hidden' => '隐藏'
    ];
    
    // 认证状态列表
    public static $verifyStatusList = [
        0 => '未认证',
        1 => '已认证',
        2 => '认证中'
    ];
    
    // 认证类型列表
    public static $verifyTypeList = [
        'personal' => '个人认证',
        'enterprise' => '企业认证',
        'media' => '媒体认证',
        'government' => '政府认证',
        'other' => '其他认证'
    ];
    
    public function getStatusTextAttr($value, $data)
    {
        return self::$statusList[$data['status']] ?? '';
    }
    
    public function getVerifyStatusTextAttr($value, $data)
    {
        return self::$verifyStatusList[$data['verify_status']] ?? '未认证';
    }
    
    public function getAvatarTextAttr($value, $data)
    {
        $avatar = $data['avatar'] ?? '';
        if (empty($avatar)) {
            // 返回默认头像
            return '/assets/img/avatar.png';
        }
        if (strpos($avatar, 'http') !== 0) {
            // 添加CDN前缀
            $avatar = cdnurl($avatar, true);
        }
        return $avatar;
    }
    
    /**
     * 关联视频
     */
    public function videos()
    {
        return $this->hasMany('Video', 'user_id', 'id');
    }
    
    /**
     * 获取发布者列表（用于selectpage）
     */
    public static function getAuthorList($where = [])
    {
        $list = self::where('status', 'normal')
            ->where($where)
            ->field('id, name, nickname, avatar, verify_status')
            ->order('weigh', 'desc')
            ->order('id', 'asc')
            ->select();
        
        return $list;
    }
    
    /**
     * 更新统计数据
     */
    public function updateStats()
    {
        // 统计视频数量
        $videoCount = \think\Db::name('video')
            ->where('user_id', $this->id)
            ->where('status', 1)
            ->count();
        
        // 统计总播放量
        $totalViews = \think\Db::name('video')
            ->where('user_id', $this->id)
            ->where('status', 1)
            ->sum('view_count');
        
        // 统计总点赞数
        $totalLikes = \think\Db::name('video')
            ->where('user_id', $this->id)
            ->where('status', 1)
            ->sum('like_count');
        
        // 统计总获得金币
        $totalCoins = \think\Db::name('video')
            ->where('user_id', $this->id)
            ->where('status', 1)
            ->sum('reward_coin_total');
        
        $this->video_count = $videoCount;
        $this->total_views = $totalViews ?: 0;
        $this->total_likes = $totalLikes ?: 0;
        $this->total_coins = $totalCoins ?: 0;
        
        return $this->save();
    }
    
    /**
     * 获取认证标识HTML
     */
    public function getVerifyBadgeAttr($value, $data)
    {
        $verifyStatus = $data['verify_status'] ?? 0;
        $verifyType = $data['verify_type'] ?? '';
        
        if ($verifyStatus != 1) {
            return '';
        }
        
        $typeLabels = [
            'personal' => '<span class="verify-badge personal" title="个人认证"><i class="fa fa-check-circle"></i></span>',
            'enterprise' => '<span class="verify-badge enterprise" title="企业认证"><i class="fa fa-check-circle"></i></span>',
            'media' => '<span class="verify-badge media" title="媒体认证"><i class="fa fa-check-circle"></i></span>',
            'government' => '<span class="verify-badge government" title="政府认证"><i class="fa fa-check-circle"></i></span>',
            'other' => '<span class="verify-badge other" title="已认证"><i class="fa fa-check-circle"></i></span>',
        ];
        
        return $typeLabels[$verifyType] ?? '<span class="verify-badge" title="已认证"><i class="fa fa-check-circle"></i></span>';
    }
}
