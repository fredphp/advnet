import { NextResponse } from 'next/server';
import { db } from '@/lib/db';

// 获取佣金统计列表
export async function GET(request: Request) {
  try {
    const { searchParams } = new URL(request.url);
    const search = searchParams.get('search') || '';

    // 获取所有会员
    const members = await db.member.findMany({
      include: {
        parent: true
      }
    });

    // 计算每个会员的佣金统计
    const result = await Promise.all(members.map(async (member) => {
      // 获取该会员的佣金统计
      const commissions = await db.commissionLog.findMany({
        where: {
          parentId: member.memberId
        }
      });

      // 计算已结算佣金
      const settledAmount = commissions
        .filter(c => c.status === 1)
        .reduce((sum, c) => sum + c.amount, 0);

      // 计算待结算佣金
      const pendingAmount = commissions
        .filter(c => c.status === 0)
        .reduce((sum, c) => sum + c.amount, 0);

      // 计算一级佣金
      const level1Commission = commissions
        .filter(c => c.level === 1 && c.status === 1)
        .reduce((sum, c) => sum + c.amount, 0);

      // 计算二级佣金
      const level2Commission = commissions
        .filter(c => c.level === 2 && c.status === 1)
        .reduce((sum, c) => sum + c.amount, 0);

      // 获取下级数量
      const level1Count = await db.member.count({
        where: { parentId: member.memberId }
      });

      // 获取二级下级
      const level1Members = await db.member.findMany({
        where: { parentId: member.memberId },
        select: { memberId: true }
      });
      const level1Ids = level1Members.map(m => m.memberId);
      const level2Count = await db.member.count({
        where: { parentId: { in: level1Ids } }
      });

      return {
        id: member.id,
        memberId: member.memberId,
        name: member.name,
        level: member.level,
        parent: member.parent ? {
          memberId: member.parent.memberId,
          name: member.parent.name
        } : null,
        balance: member.balance,
        level1Count,
        level2Count,
        totalInviteCount: level1Count + level2Count,
        settledCommission: settledAmount,
        pendingCommission: pendingAmount,
        totalCommission: settledAmount + pendingAmount,
        level1Commission,
        level2Commission,
        commissionCount: commissions.length
      };
    }));

    // 搜索过滤
    let filtered = result;
    if (search) {
      filtered = filtered.filter(m => 
        m.memberId.includes(search) || 
        m.name.includes(search)
      );
    }

    // 按总佣金排序
    filtered.sort((a, b) => b.totalCommission - a.totalCommission);

    return NextResponse.json({
      success: true,
      data: filtered
    });
  } catch (error) {
    console.error('Get commission stats error:', error);
    return NextResponse.json(
      { success: false, error: '获取佣金统计失败' },
      { status: 500 }
    );
  }
}
