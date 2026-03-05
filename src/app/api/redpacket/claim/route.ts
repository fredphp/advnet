import { NextRequest, NextResponse } from 'next/server';
import { claimRedPacket } from '@/lib/redpacket-service';

/**
 * 领取红包接口
 * POST /api/redpacket/claim
 * 
 * 根据用户在 click 接口中累积的红包金额进行领取
 * 领取后金额将转入用户余额
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

    // 执行领取红包逻辑
    const result = await claimRedPacket(userId);

    return NextResponse.json({
      success: result.success,
      data: {
        amount: result.amount,
      },
      message: result.message,
    });
  } catch (error) {
    console.error('领取红包失败:', error);
    return NextResponse.json(
      { success: false, message: '服务器错误' },
      { status: 500 }
    );
  }
}
