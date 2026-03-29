<?php

namespace app\common\validate;

use think\Validate;

/**
 * 风控规则验证器
 */
class RiskRule extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'rule_name' => 'require|max:100',
        'rule_code' => 'require|max:50|regex:[A-Za-z0-9_]+',
        'rule_type' => 'require|in:register,login,withdraw,recharge,transfer,behavior,device,ip,custom',
        'description' => 'max:500',
        'threshold' => 'float|egt:0',
        'score_weight' => 'integer|between:0,100',
        'level' => 'integer|between:1,5',
        'action' => 'in:warn,block,freeze,ban',
        'action_duration' => 'integer|egt:0',
        'enabled' => 'in:0,1',
    ];

    /**
     * 字段描述
     */
    protected $field = [
        'rule_name' => '规则名称',
        'rule_code' => '规则代码',
        'rule_type' => '规则类型',
        'description' => '规则描述',
        'threshold' => '触发阈值',
        'score_weight' => '分数权重',
        'level' => '风险等级',
        'action' => '处理动作',
        'action_duration' => '动作持续时间',
        'enabled' => '是否启用',
    ];

    /**
     * 提示消息
     */
    protected $message = [
        'rule_name.require' => '规则名称不能为空',
        'rule_name.max' => '规则名称最多100个字符',
        'rule_code.require' => '规则代码不能为空',
        'rule_code.max' => '规则代码最多50个字符',
        'rule_code.regex' => '规则代码只能包含字母、数字和下划线',
        'rule_type.require' => '规则类型不能为空',
        'rule_type.in' => '规则类型不正确',
        'description.max' => '规则描述最多500个字符',
        'threshold.float' => '触发阈值必须是数字',
        'threshold.egt' => '触发阈值必须大于等于0',
        'score_weight.integer' => '分数权重必须是整数',
        'score_weight.between' => '分数权重必须在0-100之间',
        'level.integer' => '风险等级必须是整数',
        'level.between' => '风险等级必须在1-5之间',
        'action.in' => '处理动作不正确',
        'action_duration.integer' => '动作持续时间必须是整数',
        'action_duration.egt' => '动作持续时间必须大于等于0',
        'enabled.in' => '是否启用参数不正确',
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'add' => ['rule_name', 'rule_code', 'rule_type', 'description', 'threshold', 'score_weight', 'level', 'action', 'action_duration', 'enabled'],
        'edit' => ['rule_name', 'rule_type', 'description', 'threshold', 'score_weight', 'level', 'action', 'action_duration', 'enabled'],
    ];
}
