<?php

namespace app\common\library;

use think\Model;

/**
 * 软删除 Trait
 * 
 * 使用方法：在模型中 use SoftDeleteTrait;
 */
trait SoftDeleteTrait
{
    /**
     * 删除时间字段
     */
    protected $deleteTime = 'deletetime';
    
    /**
     * 默认软删除值
     */
    protected $defaultSoftDelete = null;
    
    /**
     * 查询包含已删除的记录
     */
    public static function withTrashed()
    {
        return (new static())->where('deletetime', 'exp', \think\Db::raw('IS NULL OR deletetime IS NOT NULL'));
    }
    
    /**
     * 查询仅已删除的记录
     */
    public static function onlyTrashed()
    {
        return (new static())->where('deletetime', '<>', null);
    }
    
    /**
     * 软删除
     */
    public function softDelete()
    {
        $this->{$this->deleteTime} = time();
        return $this->save();
    }
    
    /**
     * 恢复软删除
     */
    public function restore()
    {
        $this->{$this->deleteTime} = null;
        return $this->save();
    }
    
    /**
     * 真实删除
     */
    public function forceDelete()
    {
        return parent::delete();
    }
    
    /**
     * 重写删除方法，默认使用软删除
     */
    public function delete($force = false)
    {
        if ($force) {
            return $this->forceDelete();
        }
        
        return $this->softDelete();
    }
    
    /**
     * 判断是否已删除
     */
    public function trashed()
    {
        return !is_null($this->{$this->deleteTime});
    }
    
    /**
     * 查询作用域：仅未删除
     */
    public function scopeNotTrashed($query)
    {
        return $query->whereNull($this->deleteTime);
    }
    
    /**
     * 查询作用域：仅已删除
     */
    public function scopeTrashed($query)
    {
        return $query->whereNotNull($this->deleteTime);
    }
}
