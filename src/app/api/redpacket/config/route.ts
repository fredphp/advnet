import { NextRequest, NextResponse } from 'next/server';
import { db } from '@/lib/db';
import { refreshConfigCache } from '@/lib/redpacket-service';

/**
 * 获取红包配置列表
 * GET /api/redpacket/config
 */
export async function GET() {
  try {
    const configs = await db.redPacketRewardConfig.findMany({
      orderBy: [{ minAmount: 'asc' }, { startTime: 'asc' }],
    });

    return NextResponse.json({
      success: true,
      data: configs,
    });
  } catch (error) {
    console.error('获取配置失败:', error);
    return NextResponse.json(
      { success: false, message: '服务器错误' },
      { status: 500 }
    );
  }
}

/**
 * 创建/更新红包配置
 * POST /api/redpacket/config
 */
export async function POST(request: NextRequest) {
  try {
    const body = await request.json();
    const {
      id,
      name,
      minAmount,
      maxAmount,
      startTime,
      endTime,
      baseMin,
      baseMax,
      accumulateMin,
      accumulateMax,
      maxReward,
      status,
    } = body;

    let config;

    if (id) {
      // 更新
      config = await db.redPacketRewardConfig.update({
        where: { id },
        data: {
          name,
          minAmount,
          maxAmount,
          startTime,
          endTime,
          baseMin,
          baseMax,
          accumulateMin,
          accumulateMax,
          maxReward,
          status: status ?? 1,
        },
      });
    } else {
      // 创建
      config = await db.redPacketRewardConfig.create({
        data: {
          name,
          minAmount,
          maxAmount,
          startTime,
          endTime,
          baseMin,
          baseMax,
          accumulateMin,
          accumulateMax,
          maxReward,
          status: status ?? 1,
        },
      });
    }

    // 刷新配置缓存
    await refreshConfigCache();

    return NextResponse.json({
      success: true,
      data: config,
      message: id ? '配置更新成功' : '配置创建成功',
    });
  } catch (error) {
    console.error('保存配置失败:', error);
    return NextResponse.json(
      { success: false, message: '服务器错误' },
      { status: 500 }
    );
  }
}

/**
 * 删除红包配置
 * DELETE /api/redpacket/config?id=xxx
 */
export async function DELETE(request: NextRequest) {
  try {
    const { searchParams } = new URL(request.url);
    const id = searchParams.get('id');

    if (!id) {
      return NextResponse.json(
        { success: false, message: '配置ID不能为空' },
        { status: 400 }
      );
    }

    await db.redPacketRewardConfig.delete({
      where: { id },
    });

    // 刷新配置缓存
    await refreshConfigCache();

    return NextResponse.json({
      success: true,
      message: '配置删除成功',
    });
  } catch (error) {
    console.error('删除配置失败:', error);
    return NextResponse.json(
      { success: false, message: '服务器错误' },
      { status: 500 }
    );
  }
}
