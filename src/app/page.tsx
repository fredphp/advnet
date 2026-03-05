'use client';

import { useState, useEffect, useCallback } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Separator } from '@/components/ui/separator';
import { 
  Gift, 
  Coins, 
  User, 
  Settings, 
  History, 
  Plus, 
  RefreshCw,
  CheckCircle,
  XCircle,
  Clock,
  TrendingUp
} from 'lucide-react';
import { toast } from 'sonner';

// 类型定义
interface UserInfo {
  id: string;
  email: string;
  name: string;
  balance: number;
  todayClaimedAmount: number;
}

interface RedPacketConfig {
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

interface RedPacketAmount {
  amount: number;
  baseAmount: number;
  isValid: boolean;
}

interface ClickResult {
  amount: number;
  baseAmount: number;
  isBaseGenerated: boolean;
  todayClaimedAmount: number;
}

export default function RedPacketPage() {
  // 状态
  const [currentUser, setCurrentUser] = useState<UserInfo | null>(null);
  const [users, setUsers] = useState<UserInfo[]>([]);
  const [redPacketAmount, setRedPacketAmount] = useState<RedPacketAmount>({ amount: 0, baseAmount: 0, isValid: false });
  const [configs, setConfigs] = useState<RedPacketConfig[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [isClicking, setIsClicking] = useState(false);
  const [isClaiming, setIsClaiming] = useState(false);

  // 新用户表单
  const [newUserEmail, setNewUserEmail] = useState('');
  const [newUserName, setNewUserName] = useState('');

  // 新配置表单
  const [editingConfig, setEditingConfig] = useState<Partial<RedPacketConfig>>({
    name: '',
    minAmount: 0,
    maxAmount: 200000,
    startTime: 0,
    endTime: 12,
    baseMin: 4000,
    baseMax: 6000,
    accumulateMin: 2000,
    accumulateMax: 4000,
    maxReward: 50000,
    status: 1,
  });

  // 加载用户列表
  const loadUsers = useCallback(async () => {
    try {
      const res = await fetch('/api/user');
      const data = await res.json();
      if (data.success) {
        setUsers(data.data);
      }
    } catch {
      toast.error('加载用户列表失败');
    }
  }, []);

  // 加载配置列表
  const loadConfigs = useCallback(async () => {
    try {
      const res = await fetch('/api/redpacket/config');
      const data = await res.json();
      if (data.success) {
        setConfigs(data.data);
      }
    } catch {
      toast.error('加载配置列表失败');
    }
  }, []);

  // 加载红包金额
  const loadRedPacketAmount = useCallback(async () => {
    if (!currentUser) return;
    try {
      const res = await fetch(`/api/redpacket/amount?userId=${currentUser.id}`);
      const data = await res.json();
      if (data.success) {
        setRedPacketAmount(data.data);
      }
    } catch {
      toast.error('加载红包金额失败');
    }
  }, [currentUser]);

  // 加载用户详情
  const loadUserDetail = useCallback(async (userId: string) => {
    try {
      const res = await fetch(`/api/user?userId=${userId}`);
      const data = await res.json();
      if (data.success) {
        setCurrentUser(data.data);
      }
    } catch {
      toast.error('加载用户详情失败');
    }
  }, []);

  // 初始化
  useEffect(() => {
    loadUsers();
    loadConfigs();
  }, [loadUsers, loadConfigs]);

  // 当用户变化时加载红包金额
  useEffect(() => {
    if (currentUser) {
      loadRedPacketAmount();
    }
  }, [currentUser, loadRedPacketAmount]);

  // 创建用户
  const handleCreateUser = async () => {
    if (!newUserEmail) {
      toast.error('请输入邮箱');
      return;
    }

    setIsLoading(true);
    try {
      const res = await fetch('/api/user', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: newUserEmail, name: newUserName }),
      });
      const data = await res.json();
      if (data.success) {
        toast.success('用户创建成功');
        setNewUserEmail('');
        setNewUserName('');
        loadUsers();
        setCurrentUser(data.data);
      } else {
        toast.error(data.message);
      }
    } catch {
      toast.error('创建用户失败');
    } finally {
      setIsLoading(false);
    }
  };

  // 选择用户
  const handleSelectUser = (user: UserInfo) => {
    loadUserDetail(user.id);
  };

  // 点击红包
  const handleClickRedPacket = async () => {
    if (!currentUser) {
      toast.error('请先选择用户');
      return;
    }

    setIsClicking(true);
    try {
      const res = await fetch('/api/redpacket/click', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ userId: currentUser.id }),
      });
      const data = await res.json();
      if (data.success) {
        const result = data.data as ClickResult;
        setRedPacketAmount({
          amount: result.amount,
          baseAmount: result.baseAmount,
          isValid: true,
        });
        
        if (result.isBaseGenerated) {
          toast.success(`生成基础金额: ${result.baseAmount} 金币`);
        } else {
          toast.success(`累加成功! 当前金额: ${result.amount} 金币`);
        }
      } else {
        toast.error(data.message);
      }
    } catch {
      toast.error('点击红包失败');
    } finally {
      setIsClicking(false);
    }
  };

  // 领取红包
  const handleClaimRedPacket = async () => {
    if (!currentUser) {
      toast.error('请先选择用户');
      return;
    }

    if (!redPacketAmount.isValid || redPacketAmount.amount <= 0) {
      toast.error('没有可领取的红包');
      return;
    }

    setIsClaiming(true);
    try {
      const res = await fetch('/api/redpacket/claim', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ userId: currentUser.id }),
      });
      const data = await res.json();
      if (data.success) {
        toast.success(`成功领取 ${data.data.amount} 金币!`);
        setRedPacketAmount({ amount: 0, baseAmount: 0, isValid: false });
        // 刷新用户信息
        loadUserDetail(currentUser.id);
      } else {
        toast.error(data.message);
      }
    } catch {
      toast.error('领取红包失败');
    } finally {
      setIsClaiming(false);
    }
  };

  // 保存配置
  const handleSaveConfig = async () => {
    setIsLoading(true);
    try {
      const res = await fetch('/api/redpacket/config', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(editingConfig),
      });
      const data = await res.json();
      if (data.success) {
        toast.success(data.message);
        loadConfigs();
        setEditingConfig({
          name: '',
          minAmount: 0,
          maxAmount: 200000,
          startTime: 0,
          endTime: 12,
          baseMin: 4000,
          baseMax: 6000,
          accumulateMin: 2000,
          accumulateMax: 4000,
          maxReward: 50000,
          status: 1,
        });
      } else {
        toast.error(data.message);
      }
    } catch {
      toast.error('保存配置失败');
    } finally {
      setIsLoading(false);
    }
  };

  // 删除配置
  const handleDeleteConfig = async (id: string) => {
    try {
      const res = await fetch(`/api/redpacket/config?id=${id}`, {
        method: 'DELETE',
      });
      const data = await res.json();
      if (data.success) {
        toast.success('配置删除成功');
        loadConfigs();
      } else {
        toast.error(data.message);
      }
    } catch {
      toast.error('删除配置失败');
    }
  };

  // 编辑配置
  const handleEditConfig = (config: RedPacketConfig) => {
    setEditingConfig(config);
  };

  // 获取当前时间信息
  const getCurrentTimeInfo = () => {
    const now = new Date();
    const hour = now.getHours();
    return {
      hour,
      timeStr: now.toLocaleTimeString('zh-CN'),
      dateStr: now.toLocaleDateString('zh-CN'),
    };
  };

  const timeInfo = getCurrentTimeInfo();

  return (
    <div className="min-h-screen flex flex-col bg-gradient-to-b from-red-50 to-orange-50 dark:from-gray-900 dark:to-gray-950">
      {/* Header */}
      <header className="border-b bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm sticky top-0 z-50">
        <div className="container mx-auto px-4 py-4 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="bg-red-500 p-2 rounded-full">
              <Gift className="h-6 w-6 text-white" />
            </div>
            <div>
              <h1 className="text-xl font-bold text-red-600 dark:text-red-400">红包系统</h1>
              <p className="text-xs text-muted-foreground">Red Packet System</p>
            </div>
          </div>
          <div className="flex items-center gap-4 text-sm text-muted-foreground">
            <div className="flex items-center gap-1">
              <Clock className="h-4 w-4" />
              <span>{timeInfo.dateStr} {timeInfo.timeStr}</span>
            </div>
            <Badge variant="outline">当前时段: {timeInfo.hour}:00 - {timeInfo.hour + 1}:00</Badge>
          </div>
        </div>
      </header>

      {/* Main Content */}
      <main className="flex-1 container mx-auto px-4 py-6">
        <Tabs defaultValue="redpacket" className="space-y-6">
          <TabsList className="grid w-full grid-cols-3 lg:w-auto lg:inline-grid">
            <TabsTrigger value="redpacket" className="flex items-center gap-2">
              <Gift className="h-4 w-4" />
              红包
            </TabsTrigger>
            <TabsTrigger value="user" className="flex items-center gap-2">
              <User className="h-4 w-4" />
              用户
            </TabsTrigger>
            <TabsTrigger value="config" className="flex items-center gap-2">
              <Settings className="h-4 w-4" />
              配置
            </TabsTrigger>
          </TabsList>

          {/* 红包 Tab */}
          <TabsContent value="redpacket" className="space-y-6">
            <div className="grid lg:grid-cols-2 gap-6">
              {/* 用户信息卡片 */}
              <Card>
                <CardHeader>
                  <CardTitle className="flex items-center gap-2">
                    <User className="h-5 w-5" />
                    当前用户
                  </CardTitle>
                  <CardDescription>
                    选择或创建用户来参与红包活动
                  </CardDescription>
                </CardHeader>
                <CardContent>
                  {currentUser ? (
                    <div className="space-y-4">
                      <div className="flex items-center justify-between">
                        <div>
                          <p className="font-medium">{currentUser.name}</p>
                          <p className="text-sm text-muted-foreground">{currentUser.email}</p>
                        </div>
                        <Badge variant="secondary">ID: {currentUser.id.slice(0, 8)}...</Badge>
                      </div>
                      <Separator />
                      <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-1">
                          <p className="text-sm text-muted-foreground">账户余额</p>
                          <p className="text-2xl font-bold text-yellow-600 flex items-center gap-1">
                            <Coins className="h-5 w-5" />
                            {currentUser.balance.toLocaleString()}
                          </p>
                        </div>
                        <div className="space-y-1">
                          <p className="text-sm text-muted-foreground">今日已领</p>
                          <p className="text-2xl font-bold text-green-600 flex items-center gap-1">
                            <TrendingUp className="h-5 w-5" />
                            {currentUser.todayClaimedAmount.toLocaleString()}
                          </p>
                        </div>
                      </div>
                      <Button 
                        variant="outline" 
                        className="w-full"
                        onClick={() => setCurrentUser(null)}
                      >
                        切换用户
                      </Button>
                    </div>
                  ) : (
                    <div className="space-y-4">
                      <p className="text-muted-foreground text-center py-4">
                        请在"用户"标签页选择或创建用户
                      </p>
                      <ScrollArea className="h-48">
                        <div className="space-y-2">
                          {users.map((user) => (
                            <Button
                              key={user.id}
                              variant="ghost"
                              className="w-full justify-start"
                              onClick={() => handleSelectUser(user)}
                            >
                              <User className="h-4 w-4 mr-2" />
                              {user.name} ({user.email})
                            </Button>
                          ))}
                        </div>
                      </ScrollArea>
                    </div>
                  )}
                </CardContent>
              </Card>

              {/* 红包操作卡片 */}
              <Card className="border-red-200 dark:border-red-900">
                <CardHeader className="bg-red-500 text-white rounded-t-lg">
                  <CardTitle className="flex items-center gap-2">
                    <Gift className="h-5 w-5" />
                    红包
                  </CardTitle>
                  <CardDescription className="text-red-100">
                    点击红包累积金额，领取后转入余额
                  </CardDescription>
                </CardHeader>
                <CardContent className="pt-6">
                  <div className="space-y-6">
                    {/* 当前红包金额 */}
                    <div className="text-center p-6 bg-gradient-to-r from-red-100 to-orange-100 dark:from-red-900/30 dark:to-orange-900/30 rounded-xl">
                      <p className="text-sm text-muted-foreground mb-2">当前红包金额</p>
                      <p className="text-5xl font-bold text-red-600 flex items-center justify-center gap-2">
                        <Coins className="h-8 w-8" />
                        {redPacketAmount.amount.toLocaleString()}
                      </p>
                      {redPacketAmount.isValid && (
                        <p className="text-xs text-muted-foreground mt-2">
                          基础金额: {redPacketAmount.baseAmount.toLocaleString()}
                        </p>
                      )}
                    </div>

                    {/* 操作按钮 */}
                    <div className="grid grid-cols-2 gap-4">
                      <Button
                        size="lg"
                        className="h-16 text-lg bg-red-500 hover:bg-red-600"
                        onClick={handleClickRedPacket}
                        disabled={!currentUser || isClicking}
                      >
                        {isClicking ? (
                          <RefreshCw className="h-5 w-5 animate-spin" />
                        ) : (
                          <>
                            <Gift className="h-5 w-5 mr-2" />
                            点击红包
                          </>
                        )}
                      </Button>
                      <Button
                        size="lg"
                        className="h-16 text-lg bg-green-500 hover:bg-green-600"
                        onClick={handleClaimRedPacket}
                        disabled={!currentUser || !redPacketAmount.isValid || isClaiming}
                      >
                        {isClaiming ? (
                          <RefreshCw className="h-5 w-5 animate-spin" />
                        ) : (
                          <>
                            <CheckCircle className="h-5 w-5 mr-2" />
                            领取红包
                          </>
                        )}
                      </Button>
                    </div>

                    {/* 提示信息 */}
                    {!currentUser && (
                      <div className="flex items-center gap-2 text-sm text-amber-600 bg-amber-50 dark:bg-amber-900/20 p-3 rounded-lg">
                        <XCircle className="h-4 w-4" />
                        请先选择用户
                      </div>
                    )}
                  </div>
                </CardContent>
              </Card>
            </div>

            {/* 配置说明 */}
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <History className="h-5 w-5" />
                  当前生效配置
                </CardTitle>
              </CardHeader>
              <CardContent>
                {configs.length === 0 ? (
                  <p className="text-muted-foreground text-center py-4">
                    暂无配置，请在"配置"标签页添加
                  </p>
                ) : (
                  <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                    {configs.map((config) => (
                      <div
                        key={config.id}
                        className="p-4 border rounded-lg space-y-2"
                      >
                        <div className="flex items-center justify-between">
                          <span className="font-medium">{config.name}</span>
                          <Badge variant={config.status === 1 ? 'default' : 'secondary'}>
                            {config.status === 1 ? '启用' : '禁用'}
                          </Badge>
                        </div>
                        <div className="text-sm space-y-1 text-muted-foreground">
                          <p>今日金额: {config.minAmount.toLocaleString()} - {config.maxAmount.toLocaleString()}</p>
                          <p>时间段: {config.startTime}:00 - {config.endTime}:00</p>
                          <p>基础金额: {config.baseMin} - {config.baseMax}</p>
                          <p>累加金额: {config.accumulateMin} - {config.accumulateMax}</p>
                          <p>封顶金额: {config.maxReward.toLocaleString()}</p>
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </CardContent>
            </Card>
          </TabsContent>

          {/* 用户 Tab */}
          <TabsContent value="user" className="space-y-6">
            <div className="grid lg:grid-cols-2 gap-6">
              {/* 创建用户 */}
              <Card>
                <CardHeader>
                  <CardTitle className="flex items-center gap-2">
                    <Plus className="h-5 w-5" />
                    创建用户
                  </CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="space-y-2">
                    <Label htmlFor="email">邮箱</Label>
                    <Input
                      id="email"
                      type="email"
                      placeholder="user@example.com"
                      value={newUserEmail}
                      onChange={(e) => setNewUserEmail(e.target.value)}
                    />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="name">昵称 (可选)</Label>
                    <Input
                      id="name"
                      placeholder="昵称"
                      value={newUserName}
                      onChange={(e) => setNewUserName(e.target.value)}
                    />
                  </div>
                  <Button 
                    className="w-full" 
                    onClick={handleCreateUser}
                    disabled={isLoading}
                  >
                    {isLoading ? (
                      <RefreshCw className="h-4 w-4 animate-spin" />
                    ) : (
                      <>
                        <Plus className="h-4 w-4 mr-2" />
                        创建用户
                      </>
                    )}
                  </Button>
                </CardContent>
              </Card>

              {/* 用户列表 */}
              <Card>
                <CardHeader>
                  <CardTitle className="flex items-center gap-2">
                    <User className="h-5 w-5" />
                    用户列表
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  <ScrollArea className="h-80">
                    <div className="space-y-2">
                      {users.map((user) => (
                        <div
                          key={user.id}
                          className={`p-3 border rounded-lg cursor-pointer transition-colors ${
                            currentUser?.id === user.id
                              ? 'border-red-500 bg-red-50 dark:bg-red-900/20'
                              : 'hover:bg-gray-50 dark:hover:bg-gray-800'
                          }`}
                          onClick={() => handleSelectUser(user)}
                        >
                          <div className="flex items-center justify-between">
                            <div>
                              <p className="font-medium">{user.name}</p>
                              <p className="text-sm text-muted-foreground">{user.email}</p>
                            </div>
                            <div className="text-right">
                              <p className="font-bold text-yellow-600">{user.balance.toLocaleString()}</p>
                              <p className="text-xs text-muted-foreground">金币</p>
                            </div>
                          </div>
                        </div>
                      ))}
                    </div>
                  </ScrollArea>
                </CardContent>
              </Card>
            </div>
          </TabsContent>

          {/* 配置 Tab */}
          <TabsContent value="config" className="space-y-6">
            <div className="grid lg:grid-cols-2 gap-6">
              {/* 添加/编辑配置 */}
              <Card>
                <CardHeader>
                  <CardTitle className="flex items-center gap-2">
                    <Settings className="h-5 w-5" />
                    {editingConfig.id ? '编辑配置' : '添加配置'}
                  </CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="grid grid-cols-2 gap-4">
                    <div className="col-span-2 space-y-2">
                      <Label htmlFor="configName">配置名称</Label>
                      <Input
                        id="configName"
                        placeholder="例如: 0-12点配置"
                        value={editingConfig.name || ''}
                        onChange={(e) => setEditingConfig({ ...editingConfig, name: e.target.value })}
                      />
                    </div>
                    <div className="space-y-2">
                      <Label htmlFor="minAmount">今日金额下限</Label>
                      <Input
                        id="minAmount"
                        type="number"
                        value={editingConfig.minAmount || 0}
                        onChange={(e) => setEditingConfig({ ...editingConfig, minAmount: parseInt(e.target.value) || 0 })}
                      />
                    </div>
                    <div className="space-y-2">
                      <Label htmlFor="maxAmount">今日金额上限</Label>
                      <Input
                        id="maxAmount"
                        type="number"
                        value={editingConfig.maxAmount || 0}
                        onChange={(e) => setEditingConfig({ ...editingConfig, maxAmount: parseInt(e.target.value) || 0 })}
                      />
                    </div>
                    <div className="space-y-2">
                      <Label htmlFor="startTime">开始时间 (小时)</Label>
                      <Input
                        id="startTime"
                        type="number"
                        min={0}
                        max={23}
                        value={editingConfig.startTime || 0}
                        onChange={(e) => setEditingConfig({ ...editingConfig, startTime: parseInt(e.target.value) || 0 })}
                      />
                    </div>
                    <div className="space-y-2">
                      <Label htmlFor="endTime">结束时间 (小时)</Label>
                      <Input
                        id="endTime"
                        type="number"
                        min={0}
                        max={24}
                        value={editingConfig.endTime || 12}
                        onChange={(e) => setEditingConfig({ ...editingConfig, endTime: parseInt(e.target.value) || 12 })}
                      />
                    </div>
                    <div className="space-y-2">
                      <Label htmlFor="baseMin">基础金额下限</Label>
                      <Input
                        id="baseMin"
                        type="number"
                        value={editingConfig.baseMin || 0}
                        onChange={(e) => setEditingConfig({ ...editingConfig, baseMin: parseInt(e.target.value) || 0 })}
                      />
                    </div>
                    <div className="space-y-2">
                      <Label htmlFor="baseMax">基础金额上限</Label>
                      <Input
                        id="baseMax"
                        type="number"
                        value={editingConfig.baseMax || 0}
                        onChange={(e) => setEditingConfig({ ...editingConfig, baseMax: parseInt(e.target.value) || 0 })}
                      />
                    </div>
                    <div className="space-y-2">
                      <Label htmlFor="accumulateMin">累加金额下限</Label>
                      <Input
                        id="accumulateMin"
                        type="number"
                        value={editingConfig.accumulateMin || 0}
                        onChange={(e) => setEditingConfig({ ...editingConfig, accumulateMin: parseInt(e.target.value) || 0 })}
                      />
                    </div>
                    <div className="space-y-2">
                      <Label htmlFor="accumulateMax">累加金额上限</Label>
                      <Input
                        id="accumulateMax"
                        type="number"
                        value={editingConfig.accumulateMax || 0}
                        onChange={(e) => setEditingConfig({ ...editingConfig, accumulateMax: parseInt(e.target.value) || 0 })}
                      />
                    </div>
                    <div className="space-y-2">
                      <Label htmlFor="maxReward">封顶金额</Label>
                      <Input
                        id="maxReward"
                        type="number"
                        value={editingConfig.maxReward || 0}
                        onChange={(e) => setEditingConfig({ ...editingConfig, maxReward: parseInt(e.target.value) || 0 })}
                      />
                    </div>
                    <div className="space-y-2">
                      <Label htmlFor="status">状态</Label>
                      <select
                        id="status"
                        className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        value={editingConfig.status || 1}
                        onChange={(e) => setEditingConfig({ ...editingConfig, status: parseInt(e.target.value) })}
                      >
                        <option value={1}>启用</option>
                        <option value={0}>禁用</option>
                      </select>
                    </div>
                  </div>
                  <div className="flex gap-2">
                    <Button 
                      className="flex-1" 
                      onClick={handleSaveConfig}
                      disabled={isLoading}
                    >
                      {isLoading ? (
                        <RefreshCw className="h-4 w-4 animate-spin" />
                      ) : (
                        <>
                          <CheckCircle className="h-4 w-4 mr-2" />
                          保存配置
                        </>
                      )}
                    </Button>
                    {editingConfig.id && (
                      <Button
                        variant="outline"
                        onClick={() => setEditingConfig({
                          name: '',
                          minAmount: 0,
                          maxAmount: 200000,
                          startTime: 0,
                          endTime: 12,
                          baseMin: 4000,
                          baseMax: 6000,
                          accumulateMin: 2000,
                          accumulateMax: 4000,
                          maxReward: 50000,
                          status: 1,
                        })}
                      >
                        取消编辑
                      </Button>
                    )}
                  </div>
                </CardContent>
              </Card>

              {/* 配置列表 */}
              <Card>
                <CardHeader>
                  <CardTitle>配置列表</CardTitle>
                </CardHeader>
                <CardContent>
                  <ScrollArea className="h-96">
                    <div className="space-y-4">
                      {configs.map((config) => (
                        <div key={config.id} className="p-4 border rounded-lg">
                          <div className="flex items-center justify-between mb-2">
                            <span className="font-medium">{config.name}</span>
                            <div className="flex items-center gap-2">
                              <Badge variant={config.status === 1 ? 'default' : 'secondary'}>
                                {config.status === 1 ? '启用' : '禁用'}
                              </Badge>
                              <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => handleEditConfig(config)}
                              >
                                编辑
                              </Button>
                              <Button
                                variant="ghost"
                                size="sm"
                                className="text-destructive"
                                onClick={() => handleDeleteConfig(config.id)}
                              >
                                删除
                              </Button>
                            </div>
                          </div>
                          <div className="grid grid-cols-2 gap-2 text-sm text-muted-foreground">
                            <p>今日金额: {config.minAmount.toLocaleString()} - {config.maxAmount.toLocaleString()}</p>
                            <p>时间段: {config.startTime}:00 - {config.endTime}:00</p>
                            <p>基础金额: {config.baseMin} - {config.baseMax}</p>
                            <p>累加金额: {config.accumulateMin} - {config.accumulateMax}</p>
                            <p>封顶金额: {config.maxReward.toLocaleString()}</p>
                          </div>
                        </div>
                      ))}
                    </div>
                  </ScrollArea>
                </CardContent>
              </Card>
            </div>
          </TabsContent>
        </Tabs>
      </main>

      {/* Footer */}
      <footer className="border-t bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm mt-auto">
        <div className="container mx-auto px-4 py-4 text-center text-sm text-muted-foreground">
          红包系统 - 根据配置动态生成红包金额
        </div>
      </footer>
    </div>
  );
}
