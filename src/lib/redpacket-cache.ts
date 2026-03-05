/**
 * 红包缓存工具
 * 使用内存缓存模拟 Redis Hash 结构
 */

// 红包点击数据结构
interface RedPacketClickData {
  amount: number;           // 当前红包总金额
  baseAmount: number;       // 基础金额
  lastTimeSegment: string;  // 上次点击的时间段（格式：start-end）
  updatedAt: number;        // 更新时间戳
}

// 红包配置缓存结构
interface RedPacketConfigCache {
  id: string;
  name: string;
  minAmount: number;
  maxAmount: number;
  startTime: number;
  endTime: number;
  baseMin: number;
  baseMax: number;
  accumulateMin: number;
  accumulateMax: number;
  maxReward: number;
  status: number;
}

// 内存缓存存储
const clickDataStore = new Map<string, RedPacketClickData>();
const configCacheStore = new Map<string, RedPacketConfigCache[]>();

// 缓存 Key 前缀
const CLICK_KEY_PREFIX = 'red_packet:click:';
const CONFIG_KEY = 'red_packet:config';

/**
 * 获取用户红包点击数据的 Key
 */
export function getClickKey(userId: string): string {
  return `${CLICK_KEY_PREFIX}${userId}`;
}

/**
 * 获取用户红包点击数据
 */
export function getClickData(userId: string): RedPacketClickData | null {
  const key = getClickKey(userId);
  const data = clickDataStore.get(key);
  return data || null;
}

/**
 * 设置用户红包点击数据
 */
export function setClickData(userId: string, data: RedPacketClickData): void {
  const key = getClickKey(userId);
  clickDataStore.set(key, { ...data, updatedAt: Date.now() });
}

/**
 * 删除用户红包点击数据
 */
export function deleteClickData(userId: string): void {
  const key = getClickKey(userId);
  clickDataStore.delete(key);
}

/**
 * 检查数据是否是今天的
 */
export function isTodayData(data: RedPacketClickData | null): boolean {
  if (!data) return false;
  const today = new Date().setHours(0, 0, 0, 0);
  const dataDate = new Date(data.updatedAt).setHours(0, 0, 0, 0);
  return today === dataDate;
}

/**
 * 获取配置缓存
 */
export function getConfigCache(): RedPacketConfigCache[] | null {
  return configCacheStore.get(CONFIG_KEY) || null;
}

/**
 * 设置配置缓存
 */
export function setConfigCache(configs: RedPacketConfigCache[]): void {
  configCacheStore.set(CONFIG_KEY, configs);
}

/**
 * 清除配置缓存
 */
export function clearConfigCache(): void {
  configCacheStore.delete(CONFIG_KEY);
}

/**
 * 获取当前时间段（小时）
 */
export function getCurrentHour(): number {
  return new Date().getHours();
}

/**
 * 获取当前时间段字符串
 */
export function getCurrentTimeSegment(start: number, end: number): string {
  return `${start}-${end}`;
}

/**
 * 判断当前时间是否在指定时间段内
 */
export function isInTimeSegment(start: number, end: number): boolean {
  const currentHour = getCurrentHour();
  return currentHour >= start && currentHour < end;
}

export type { RedPacketClickData, RedPacketConfigCache };
