<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\RedPacketTask;
use app\common\model\RedPacketRecord;
use think\Db;
use think\Exception;

/**
 * 红包接口
 */
class Redpacket extends Api
{
    // 无需登录的接口
    protected $noNeedLogin = ['list', 'detail'];
    
    // 无需鉴权的接口
    protected $noNeedRight = ['*'];
    
    /**
     * 红包列表
     */
    public function list()
    {
        $page = $this->request->get('page/d', 1);
        $limit = $this->request->get('limit/d', 20);
        $status = $this->request->get('status', 'normal');
        
        $where = [];
        if ($status) {
            $where['status'] = $status;
        }
        
        $list = RedPacketTask::with(['resource'])
            ->where($where)
            ->where('push_status', 1) // 只显示已推送的
            ->order('createtime', 'desc')
            ->paginate($limit, false, ['page' => $page]);
        
        // 处理数据
        foreach ($list as &$task) {
            $task->claimed_count = RedPacketRecord::where('task_id', $task->id)->count();
        }
        
        $this->success('获取成功', [
            'list' => $list->items(),
            'total' => $list->total(),
            'page' => $page,
            'has_more' => $list->hasMore()
        ]);
    }
    
    /**
     * 红包详情
     */
    public function detail()
    {
        $id = $this->request->get('id/d');
        if (!$id) {
            $this->error('参数错误');
        }
        
        $task = RedPacketTask::with(['resource'])->find($id);
        if (!$task) {
            $this->error('红包不存在');
        }
        
        // 领取人数
        $task->claimed_count = RedPacketRecord::where('task_id', $task->id)->count();
        
        // 是否已领取
        $userId = $this->auth->id ?? 0;
        $task->is_claimed = false;
        $task->claim_amount = 0;
        if ($userId) {
            $record = RedPacketRecord::where('task_id', $id)
                ->where('user_id', $userId)
                ->find();
            if ($record) {
                $task->is_claimed = true;
                $task->claim_amount = $record->amount;
            }
        }
        
        $this->success('获取成功', $task);
    }
    
    /**
     * 领取红包
     */
    public function claim()
    {
        $id = $this->request->post('id/d');
        if (!$id) {
            $this->error('参数错误');
        }
        
        $userId = $this->auth->id;
        if (!$userId) {
            $this->error('请先登录');
        }
        
        $task = RedPacketTask::find($id);
        if (!$task) {
            $this->error('红包不存在');
        }
        
        if ($task->status != 'normal') {
            $this->error('红包已结束');
        }
        
        // 检查是否已领取
        $exists = RedPacketRecord::where('task_id', $id)
            ->where('user_id', $userId)
            ->find();
        if ($exists) {
            $this->error('您已领取过该红包');
        }
        
        // 检查剩余数量
        if ($task->remain_count <= 0 || $task->remain_amount <= 0) {
            $task->status = 'finished';
            $task->save();
            $this->error('红包已被抢光');
        }
        
        Db::startTrans();
        try {
            // 领取金额（普通红包固定金额）
            $amount = $task->reward;
            
            // 创建领取记录
            $record = new RedPacketRecord();
            $record->task_id = $id;
            $record->user_id = $userId;
            $record->amount = $amount;
            $record->status = 1;
            $record->save();
            
            // 更新任务剩余数量
            $task->remain_count = $task->remain_count - 1;
            $task->remain_amount = $task->remain_amount - $amount;
            if ($task->remain_count <= 0) {
                $task->status = 'finished';
            }
            $task->save();
            
            // 更新用户余额
            Db::name('user')->where('id', $userId)->inc('money', $amount)->update();
            
            // 记录资金日志
            $user = \app\common\model\User::get($userId);
            Db::name('user_money_log')->insert([
                'user_id' => $userId,
                'money' => $amount,
                'before' => $user->money,
                'after' => $user->money + $amount,
                'memo' => '领取红包: ' . $task->name,
                'createtime' => time()
            ]);
            
            Db::commit();
            
            $this->success('领取成功', [
                'amount' => $amount,
                'task_name' => $task->name,
                'resource' => $task->resource
            ]);
        } catch (Exception $e) {
            Db::rollback();
            $this->error('领取失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 我的领取记录
     */
    public function records()
    {
        $userId = $this->auth->id;
        if (!$userId) {
            $this->error('请先登录');
        }
        
        $page = $this->request->get('page/d', 1);
        $limit = $this->request->get('limit/d', 20);
        
        $list = RedPacketRecord::with(['task', 'task.resource'])
            ->where('user_id', $userId)
            ->order('createtime', 'desc')
            ->paginate($limit, false, ['page' => $page]);
        
        $this->success('获取成功', [
            'list' => $list->items(),
            'total' => $list->total()
        ]);
    }
}
