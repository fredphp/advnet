import { NextRequest, NextResponse } from 'next/server';
import { db } from '@/lib/db';
import { getTodayClaimedAmount } from '@/lib/redpacket-service';

/**
 * 获取用户信息
 * GET /api/user?userId=xxx
 */
export async function GET(request: NextRequest) {
  try {
    const { searchParams } = new URL(request.url);
    const userId = searchParams.get('userId');

    if (userId) {
      const user = await db.user.findUnique({
        where: { id: userId },
        select: {
          id: true,
          email: true,
          name: true,
          balance: true,
          createdAt: true,
        },
      });

      if (!user) {
        return NextResponse.json(
          { success: false, message: '用户不存在' },
          { status: 404 }
        );
      }

      // 获取今日已领取金额
      const todayClaimedAmount = await getTodayClaimedAmount(userId);

      return NextResponse.json({
        success: true,
        data: {
          ...user,
          todayClaimedAmount,
        },
      });
    }

    // 获取所有用户
    const users = await db.user.findMany({
      select: {
        id: true,
        email: true,
        name: true,
        balance: true,
        createdAt: true,
      },
      orderBy: { createdAt: 'desc' },
    });

    return NextResponse.json({
      success: true,
      data: users,
    });
  } catch (error) {
    console.error('获取用户失败:', error);
    return NextResponse.json(
      { success: false, message: '服务器错误' },
      { status: 500 }
    );
  }
}

/**
 * 创建用户
 * POST /api/user
 */
export async function POST(request: NextRequest) {
  try {
    const body = await request.json();
    const { email, name } = body;

    if (!email) {
      return NextResponse.json(
        { success: false, message: '邮箱不能为空' },
        { status: 400 }
      );
    }

    const user = await db.user.create({
      data: {
        email,
        name: name || email.split('@')[0],
      },
    });

    return NextResponse.json({
      success: true,
      data: user,
      message: '用户创建成功',
    });
  } catch (error) {
    console.error('创建用户失败:', error);
    return NextResponse.json(
      { success: false, message: '服务器错误' },
      { status: 500 }
    );
  }
}
