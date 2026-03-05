import { NextResponse } from 'next/server';
import { db } from '@/lib/db';
import { refreshConfigCache } from '@/lib/redpacket-service';

/**
 * 初始化红包配置数据
 * GET /api/init
 */
export async function GET() {
  try {
    // 检查是否已有配置
    const existingConfigs = await db.redPacketRewardConfig.count();

    if (existingConfigs === 0) {
      // 创建默认配置
      await db.redPacketRewardConfig.createMany({
        data: [
          {
            name: '0-12点配置（低金额用户）',
            minAmount: 0,
            maxAmount: 50000,
            startTime: 0,
            endTime: 12,
            baseMin: 4000,
            baseMax: 6000,
            accumulateMin: 2000,
            accumulateMax: 4000,
            maxReward: 50000,
            status: 1,
          },
          {
            name: '0-12点配置（高金额用户）',
            minAmount: 50001,
            maxAmount: 200000,
            startTime: 0,
            endTime: 12,
            baseMin: 6000,
            baseMax: 8000,
            accumulateMin: 3000,
            accumulateMax: 5000,
            maxReward: 80000,
            status: 1,
          },
          {
            name: '12-18点配置（低金额用户）',
            minAmount: 0,
            maxAmount: 50000,
            startTime: 12,
            endTime: 18,
            baseMin: 3000,
            baseMax: 4000,
            accumulateMin: 2000,
            accumulateMax: 3000,
            maxReward: 40000,
            status: 1,
          },
          {
            name: '12-18点配置（高金额用户）',
            minAmount: 50001,
            maxAmount: 200000,
            startTime: 12,
            endTime: 18,
            baseMin: 5000,
            baseMax: 7000,
            accumulateMin: 2500,
            accumulateMax: 4000,
            maxReward: 60000,
            status: 1,
          },
          {
            name: '18-24点配置（低金额用户）',
            minAmount: 0,
            maxAmount: 50000,
            startTime: 18,
            endTime: 24,
            baseMin: 2000,
            baseMax: 3000,
            accumulateMin: 1000,
            accumulateMax: 2000,
            maxReward: 30000,
            status: 1,
          },
          {
            name: '18-24点配置（高金额用户）',
            minAmount: 50001,
            maxAmount: 200000,
            startTime: 18,
            endTime: 24,
            baseMin: 4000,
            baseMax: 5000,
            accumulateMin: 2000,
            accumulateMax: 3000,
            maxReward: 50000,
            status: 1,
          },
        ],
      });
    }

    // 创建测试用户
    const testUser = await db.user.upsert({
      where: { email: 'test@example.com' },
      update: {},
      create: {
        email: 'test@example.com',
        name: '测试用户',
        balance: 0,
      },
    });

    // 刷新配置缓存
    await refreshConfigCache();

    return NextResponse.json({
      success: true,
      message: '初始化完成',
      data: {
        testUser,
        configCount: await db.redPacketRewardConfig.count(),
      },
    });
  } catch (error) {
    console.error('初始化失败:', error);
    return NextResponse.json(
      { success: false, message: '初始化失败' },
      { status: 500 }
    );
  }
}
