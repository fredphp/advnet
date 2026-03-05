import { NextRequest, NextResponse } from 'next/server';
import { getRedPacketAmount } from '@/lib/redpacket-service';

/**
 * 获取当前红包金额接口
 * GET /api/redpacket/amount?userId=xxx
 */
export async function GET(request: NextRequest) {
  try {
    const { searchParams } = new URL(request.url);
    const userId = searchParams.get('userId');

    if (!userId) {
      return NextResponse.json(
        { success: false, message: '用户ID不能为空' },
        { status: 400 }
      );
    }

    const result = getRedPacketAmount(userId);

    return NextResponse.json({
      success: true,
      data: {
        amount: result.amount,
        baseAmount: result.baseAmount,
        isValid: result.isValid,
      },
    });
  } catch (error) {
    console.error('获取红包金额失败:', error);
    return NextResponse.json(
      { success: false, message: '服务器错误' },
      { status: 500 }
    );
  }
}
