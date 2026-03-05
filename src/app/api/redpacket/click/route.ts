import { NextRequest, NextResponse } from 'next/server';
import { clickRedPacket, getTodayClaimedAmount } from '@/lib/redpacket-service';

/**
 * 点击红包接口
 * POST /api/redpacket/click
 */
export async function POST(request: NextRequest) {
  try {
    const body = await request.json();
    const { userId } = body;

    if (!userId) {
      return NextResponse.json(
        { success: false, message: '用户ID不能为空' },
        { status: 400 }
      );
    }

    // 获取用户今日已领取金额
    const todayClaimedAmount = await getTodayClaimedAmount(userId);

    // 执行点击红包逻辑
    const result = await clickRedPacket(userId, todayClaimedAmount);

    return NextResponse.json({
      success: result.success,
      data: {
        amount: result.amount,
        baseAmount: result.baseAmount,
        isBaseGenerated: result.isBaseGenerated,
        todayClaimedAmount,
      },
      message: result.message,
    });
  } catch (error) {
    console.error('点击红包失败:', error);
    return NextResponse.json(
      { success: false, message: '服务器错误' },
      { status: 500 }
    );
  }
}
