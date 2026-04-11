#!/bin/bash
# ============================================================================
# 马上赚 (AdNetwork) 一键启动服务脚本
# ============================================================================
# 用途: 启动所有必要的服务（Web服务、队列监听、定时任务、常驻进程）
# 使用: chmod +x server.sh && ./server.sh [start|stop|restart|status]
# ============================================================================

set -e

# ==================== 配置区 ====================

# 项目根目录（脚本所在目录）
PROJECT_DIR="$(cd "$(dirname "$0")" && pwd)"

# PHP 可执行文件路径（自动检测，也可手动指定）
PHP_BIN="${PHP_BIN:-$(which php)}"

# ThinkPHP 入口
THINK_CMD="$PROJECT_DIR/think"

# 日志目录
LOG_DIR="$PROJECT_DIR/runtime/logs"
mkdir -p "$LOG_DIR"

# PID 文件目录
PID_DIR="$PROJECT_DIR/runtime/pids"
mkdir -p "$PID_DIR"

# 各服务日志文件
QUEUE_LOG="$LOG_DIR/queue.log"
CRON_WRAPPER_LOG="$LOG_DIR/cron_wrapper.log"
FEED_REWARD_LOG="$LOG_DIR/feed_reward.log"

# 服务端口
WEB_PORT="${WEB_PORT:-8080}"

# 信息流广告奖励结算间隔（秒）
FEED_REWARD_INTERVAL=5

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# ==================== 工具函数 ====================

print_banner() {
    echo -e "${GREEN}"
    echo "╔══════════════════════════════════════════════════════════╗"
    echo "║          马上赚 (AdNetwork) 服务管理脚本                 ║"
    echo "║          ThinkPHP 5.0 + FastAdmin 1.6.1                 ║"
    echo "╚══════════════════════════════════════════════════════════╝"
    echo -e "${NC}"
}

log_info() {
    echo -e "${GREEN}[INFO]${NC} $(date '+%Y-%m-%d %H:%M:%S') $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $(date '+%Y-%m-%d %H:%M:%S') $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $(date '+%Y-%m-%d %H:%M:%S') $1"
}

check_php() {
    if [ ! -f "$THINK_CMD" ]; then
        log_error "ThinkPHP 入口文件不存在: $THINK_CMD"
        exit 1
    fi
    if ! $PHP_BIN -v > /dev/null 2>&1; then
        log_error "PHP 不可用，请确认 PHP 已安装并配置正确"
        log_error "当前 PHP_BIN=$PHP_BIN"
        exit 1
    fi
    PHP_VERSION=$($PHP_BIN -r "echo PHP_VERSION;")
    log_info "PHP 版本: $PHP_VERSION"
}

check_redis() {
    if $PHP_BIN -m 2>/dev/null | grep -qi "redis"; then
        log_info "PHP Redis 扩展: 已安装"
    else
        log_warn "PHP Redis 扩展: 未安装（队列功能需要 Redis 扩展）"
    fi
    # 检查 Redis 服务是否运行
    if command -v redis-cli &> /dev/null; then
        if redis-cli ping 2>/dev/null | grep -q "PONG"; then
            log_info "Redis 服务: 运行中"
        else
            log_warn "Redis 服务: 未运行或无法连接（队列功能需要 Redis）"
        fi
    else
        log_warn "Redis CLI: 未安装（无法检测 Redis 状态）"
    fi
}

check_project() {
    if [ ! -d "$PROJECT_DIR/application" ]; then
        log_error "项目目录不正确，application 目录不存在: $PROJECT_DIR"
        exit 1
    fi
    log_info "项目目录: $PROJECT_DIR"

    # 检查 runtime 目录权限
    if [ ! -w "$PROJECT_DIR/runtime" ]; then
        log_warn "runtime 目录不可写，尝试修复权限..."
        chmod -R 755 "$PROJECT_DIR/runtime" 2>/dev/null || {
            log_error "无法设置 runtime 目录权限，请手动执行: chmod -R 755 $PROJECT_DIR/runtime"
            exit 1
        }
    fi
    log_info "runtime 目录: 可写"
}

# ==================== Web 开发服务器 ====================

start_web() {
    local pid_file="$PID_DIR/web.pid"
    if [ -f "$pid_file" ] && kill -0 "$(cat "$pid_file")" 2>/dev/null; then
        log_warn "Web 开发服务器已在运行 (PID: $(cat "$pid_file"))"
        return
    fi
    log_info "启动 Web 开发服务器 (端口: $WEB_PORT)..."
    cd "$PROJECT_DIR"
    nohup $PHP_BIN think run -p $WEB_PORT > "$LOG_DIR/web.log" 2>&1 &
    echo $! > "$pid_file"
    sleep 2
    if kill -0 "$(cat "$pid_file")" 2>/dev/null; then
        log_info "Web 开发服务器启动成功 (PID: $(cat "$pid_file"), 端口: $WEB_PORT)"
    else
        log_error "Web 开发服务器启动失败，查看日志: $LOG_DIR/web.log"
        rm -f "$pid_file"
    fi
}

stop_web() {
    local pid_file="$PID_DIR/web.pid"
    if [ -f "$pid_file" ]; then
        local pid=$(cat "$pid_file")
        if kill -0 "$pid" 2>/dev/null; then
            kill "$pid" 2>/dev/null
            sleep 1
            kill -9 "$pid" 2>/dev/null || true
            log_info "Web 开发服务器已停止 (PID: $pid)"
        fi
        rm -f "$pid_file"
    else
        log_info "Web 开发服务器未运行"
    fi
}

# ==================== 队列监听 ====================

start_queue() {
    local pid_file="$PID_DIR/queue.pid"
    if [ -f "$pid_file" ] && kill -0 "$(cat "$pid_file")" 2>/dev/null; then
        log_warn "队列监听已在运行 (PID: $(cat "$pid_file"))"
        return
    fi
    log_info "启动队列监听 (Redis Queue)..."
    cd "$PROJECT_DIR"
    nohup $PHP_BIN think queue:listen --daemon > "$QUEUE_LOG" 2>&1 &
    echo $! > "$pid_file"
    sleep 2
    if kill -0 "$(cat "$pid_file")" 2>/dev/null; then
        log_info "队列监听启动成功 (PID: $(cat "$pid_file"))"
    else
        log_error "队列监听启动失败，查看日志: $QUEUE_LOG"
        rm -f "$pid_file"
    fi
}

stop_queue() {
    local pid_file="$PID_DIR/queue.pid"
    if [ -f "$pid_file" ]; then
        local pid=$(cat "$pid_file")
        if kill -0 "$pid" 2>/dev/null; then
            kill "$pid" 2>/dev/null
            sleep 1
            kill -9 "$pid" 2>/dev/null || true
            log_info "队列监听已停止 (PID: $pid)"
        fi
        rm -f "$pid_file"
    else
        log_info "队列监听未运行"
    fi
}

# ==================== 信息流广告奖励异步结算（常驻进程） ====================
# crontab 最小粒度为1分钟，无法满足5秒执行需求，使用 while+sleep 常驻进程

start_feed_reward() {
    local pid_file="$PID_DIR/feed_reward.pid"
    if [ -f "$pid_file" ] && kill -0 "$(cat "$pid_file")" 2>/dev/null; then
        log_warn "信息流广告奖励结算已在运行 (PID: $(cat "$pid_file"))"
        return
    fi
    log_info "启动信息流广告奖励异步结算 (每${FEED_REWARD_INTERVAL}秒)..."
    cd "$PROJECT_DIR"

    # 常驻进程：while 循环 + sleep，每次执行 settle_feed
    nohup bash -c "while true; do sleep ${FEED_REWARD_INTERVAL}; cd ${PROJECT_DIR} && ${PHP_BIN} think ad:reward --action=settle_feed --limit=50 >> ${FEED_REWARD_LOG} 2>&1; done" \
        > /dev/null 2>&1 &
    echo $! > "$pid_file"
    sleep 1
    if kill -0 "$(cat "$pid_file")" 2>/dev/null; then
        log_info "信息流广告奖励结算启动成功 (PID: $(cat "$pid_file"), 间隔: ${FEED_REWARD_INTERVAL}s)"
    else
        log_error "信息流广告奖励结算启动失败，查看日志: $FEED_REWARD_LOG"
        rm -f "$pid_file"
    fi
}

stop_feed_reward() {
    local pid_file="$PID_DIR/feed_reward.pid"
    if [ -f "$pid_file" ]; then
        local pid=$(cat "$pid_file")
        if kill -0 "$pid" 2>/dev/null; then
            # 杀掉 bash 子进程及其所有子进程
            pkill -P "$pid" 2>/dev/null || true
            kill "$pid" 2>/dev/null
            sleep 1
            kill -9 "$pid" 2>/dev/null || true
            log_info "信息流广告奖励结算已停止 (PID: $pid)"
        fi
        rm -f "$pid_file"
    else
        log_info "信息流广告奖励结算未运行"
    fi
}

# ==================== 定时任务管理 ====================

CRON_TAG="advnet_cron"
CRON_FILE="/tmp/advnet_cron_${USER}.txt"

generate_crontab() {
    cat > "$CRON_FILE" << 'CRON_EOF'
# ============================================================================
# 马上赚 (AdNetwork) 定时任务配置
# 由 server.sh 自动生成和管理，请勿手动编辑
# ============================================================================

# ─── 信息流广告奖励异步结算（每5秒，常驻进程，非cron）───
# 由 start/stop 控制，见 start_feed_reward / stop_feed_reward

# ─── 每日统计重置（每天0点）───
0 0 * * * cd __PROJECT_DIR__ && __PHP_BIN__ think invite:commission --action=daily >> __LOG_DIR__/cron_daily.log 2>&1

# ─── 每周统计重置（每周一0点）───
0 0 * * 1 cd __PROJECT_DIR__ && __PHP_BIN__ think invite:commission --action=weekly >> __LOG_DIR__/cron_weekly.log 2>&1

# ─── 每月统计重置（每月1号0点）───
0 0 1 * * cd __PROJECT_DIR__ && __PHP_BIN__ think invite:commission --action=monthly >> __LOG_DIR__/cron_monthly.log 2>&1

# ─── 分佣汇总统计（每天2点）───
0 2 * * * cd __PROJECT_DIR__ && __PHP_BIN__ think invite:commission --action=summary >> __LOG_DIR__/cron_summary.log 2>&1

# ─── 周期统计更新（每天0:30）───
30 0 * * * cd __PROJECT_DIR__ && __PHP_BIN__ think invite:commission --action=period >> __LOG_DIR__/cron_period.log 2>&1

# ─── 冻结分佣检查（每天3点，仅告警）───
0 3 * * * cd __PROJECT_DIR__ && __PHP_BIN__ think invite:commission --action=frozen >> __LOG_DIR__/cron_frozen.log 2>&1

# ─── 过期记录清理（每周日4点）───
0 4 * * 0 cd __PROJECT_DIR__ && __PHP_BIN__ think invite:commission --action=clean >> __LOG_DIR__/cron_clean.log 2>&1

# ─── 广告收入结算（每30分钟）───
*/30 * * * * cd __PROJECT_DIR__ && __PHP_BIN__ think ad:settle --action=settle --limit=500 >> __LOG_DIR__/cron_ad_settle.log 2>&1

# ─── 过期广告红包处理（每小时）───
0 * * * * cd __PROJECT_DIR__ && __PHP_BIN__ think ad:settle --action=expire >> __LOG_DIR__/cron_ad_expire.log 2>&1

# ─── 分表预创建（每月1号0:05）───
5 0 1 * * cd __PROJECT_DIR__ && __PHP_BIN__ think split:create-tables --months=2 >> __LOG_DIR__/cron_split_tables.log 2>&1

# ─── 数据迁移归档（每天3:30）───
30 3 * * * cd __PROJECT_DIR__ && __PHP_BIN__ think data:migrate --action=all >> __LOG_DIR__/cron_migration.log 2>&1
CRON_EOF

    # 替换占位符
    sed -i "s|__PROJECT_DIR__|$PROJECT_DIR|g" "$CRON_FILE"
    sed -i "s|__PHP_BIN__|$PHP_BIN|g" "$CRON_FILE"
    sed -i "s|__LOG_DIR__|$LOG_DIR|g" "$CRON_FILE"
}

install_crontab() {
    log_info "配置定时任务..."

    # 先移除旧的定时任务
    crontab -l 2>/dev/null | grep -v "$CRON_TAG" | grep -v "think invite:commission" | grep -v "think ad:settle" | grep -v "think ad:reward" | grep -v "think split:create-tables" | grep -v "think data:migrate" > /tmp/advnet_cron_backup_${USER}.txt 2>/dev/null || true

    # 生成新的定时任务
    generate_crontab

    # 合并定时任务（保留其他用户的定时任务）
    if [ -s /tmp/advnet_cron_backup_${USER}.txt ]; then
        cat /tmp/advnet_cron_backup_${USER}.txt "$CRON_FILE" | crontab -
    else
        crontab "$CRON_FILE"
    fi

    # 清理临时文件
    rm -f /tmp/advnet_cron_backup_${USER}.txt

    log_info "定时任务配置完成，共 $(grep -c '^[^#]' "$CRON_FILE") 条任务"
    log_info "定时任务列表:"
    grep -v '^#\|^$\|^#' "$CRON_FILE" | while read line; do
        if [ -n "$line" ]; then
            log_info "  $line"
        fi
    done
}

uninstall_crontab() {
    log_info "移除定时任务..."
    crontab -l 2>/dev/null | grep -v "think invite:commission" | grep -v "think ad:settle" | grep -v "think ad:reward" | grep -v "think split:create-tables" | grep -v "think data:migrate" | crontab - 2>/dev/null || true
    rm -f "$CRON_FILE"
    log_info "定时任务已移除"
}

# ==================== 目录权限 ====================

fix_permissions() {
    log_info "修复目录权限..."
    chmod -R 755 "$PROJECT_DIR/runtime" 2>/dev/null || true
    if [ -d "$PROJECT_DIR/public/uploads" ]; then
        chmod -R 755 "$PROJECT_DIR/public/uploads" 2>/dev/null || true
    fi
    log_info "目录权限修复完成"
}

# ==================== 主命令 ====================

do_start() {
    print_banner
    check_project
    check_php
    check_redis
    fix_permissions

    echo ""
    log_info "========== 启动所有服务 =========="

    # 1. 启动 Web 开发服务器
    start_web

    # 2. 启动队列监听
    start_queue

    # 3. 启动信息流广告奖励异步结算（常驻进程，每5秒）
    start_feed_reward

    # 4. 安装定时任务
    install_crontab

    echo ""
    log_info "========== 服务启动完成 =========="
    echo ""
    echo -e "${GREEN}服务状态:${NC}"
    echo "  Web 开发服务器:   http://localhost:$WEB_PORT"
    echo "  后台管理:         http://localhost:$WEB_PORT/admin"
    echo "  API 接口:         http://localhost:$WEB_PORT/api"
    echo "  队列监听:         运行中"
    echo "  广告奖励结算:     运行中 (每${FEED_REWARD_INTERVAL}秒)"
    echo "  定时任务:         已配置"
    echo ""
    echo -e "${YELLOW}注意: 生产环境请使用 Nginx + PHP-FPM 部署 Web 服务${NC}"
    echo -e "${YELLOW}注意: websocket 和 autopush 服务暂未启用${NC}"
    echo ""
}

do_stop() {
    print_banner
    echo ""
    log_info "========== 停止所有服务 =========="

    stop_web
    stop_queue
    stop_feed_reward
    uninstall_crontab

    echo ""
    log_info "========== 所有服务已停止 =========="
}

do_restart() {
    do_stop
    sleep 2
    do_start
}

do_status() {
    print_banner
    echo ""

    # Web 服务状态
    local web_pid_file="$PID_DIR/web.pid"
    if [ -f "$web_pid_file" ] && kill -0 "$(cat "$web_pid_file")" 2>/dev/null; then
        echo -e "  Web 开发服务器:   ${GREEN}运行中${NC} (PID: $(cat "$web_pid_file"), 端口: $WEB_PORT)"
    else
        echo -e "  Web 开发服务器:   ${RED}未运行${NC}"
    fi

    # 队列状态
    local queue_pid_file="$PID_DIR/queue.pid"
    if [ -f "$queue_pid_file" ] && kill -0 "$(cat "$queue_pid_file")" 2>/dev/null; then
        echo -e "  队列监听:         ${GREEN}运行中${NC} (PID: $(cat "$queue_pid_file"))"
    else
        echo -e "  队列监听:         ${RED}未运行${NC}"
    fi

    # 信息流广告奖励结算状态
    local feed_pid_file="$PID_DIR/feed_reward.pid"
    if [ -f "$feed_pid_file" ] && kill -0 "$(cat "$feed_pid_file")" 2>/dev/null; then
        echo -e "  广告奖励结算:     ${GREEN}运行中${NC} (PID: $(cat "$feed_pid_file"), 间隔: ${FEED_REWARD_INTERVAL}s)"
    else
        echo -e "  广告奖励结算:     ${RED}未运行${NC}"
    fi

    # 定时任务状态
    local cron_count=$(crontab -l 2>/dev/null | grep "think invite:commission\|think ad:settle\|think ad:reward\|think split:create-tables\|think data:migrate" | wc -l)
    if [ "$cron_count" -gt 0 ]; then
        echo -e "  定时任务:         ${GREEN}已配置${NC} ($cron_count 条任务)"
    else
        echo -e "  定时任务:         ${RED}未配置${NC}"
    fi

    echo ""
}

show_help() {
    print_banner
    echo ""
    echo "用法: $0 [命令]"
    echo ""
    echo "命令:"
    echo "  start     启动所有服务（Web、队列、广告奖励结算、定时任务）"
    echo "  stop      停止所有服务"
    echo "  restart   重启所有服务"
    echo "  status    查看所有服务状态"
    echo "  help      显示帮助信息"
    echo ""
    echo "环境变量:"
    echo "  PHP_BIN              指定 PHP 可执行文件路径 (默认: 自动检测)"
    echo "  WEB_PORT             指定 Web 开发服务器端口 (默认: 8080)"
    echo "  FEED_REWARD_INTERVAL 信息流广告奖励结算间隔秒数 (默认: 5)"
    echo ""
    echo "示例:"
    echo "  $0 start              # 启动所有服务"
    echo "  $0 status             # 查看状态"
    echo "  PHP_BIN=/usr/bin/php7.4 $0 start  # 指定 PHP 版本"
    echo "  WEB_PORT=9000 $0 start            # 指定端口"
    echo ""
    echo "常驻进程:"
    echo "  ┌──────────────────┬────────┬──────────────────────────────────────────┐"
    echo "  │ 服务             │ 频率   │ 说明                                     │"
    echo "  ├──────────────────┼────────┼──────────────────────────────────────────┤"
    echo "  │ 广告奖励结算     │ 每5秒  │ php think ad:reward --action=settle_feed │"
    echo "  └──────────────────┴────────┴──────────────────────────────────────────┘"
    echo ""
    echo "定时任务:"
    echo "  ┌──────────────┬──────────┬──────────────────────────────────────┐"
    echo "  │ 任务         │ 频率     │ 说明                                 │"
    echo "  ├──────────────┼──────────┼──────────────────────────────────────┤"
    echo "  │ 每日统计重置 │ 每天0点  │ php think invite:commission daily    │"
    echo "  │ 每周统计重置 │ 每周一0点│ php think invite:commission weekly   │"
    echo "  │ 每月统计重置 │ 每月1号  │ php think invite:commission monthly  │"
    echo "  │ 分佣汇总     │ 每天2点  │ php think invite:commission summary  │"
    echo "  │ 周期统计     │ 每天0:30 │ php think invite:commission period   │"
    echo "  │ 冻结分佣检查 │ 每天3点  │ php think invite:commission frozen   │"
    echo "  │ 记录清理     │ 每周日4点│ php think invite:commission clean    │"
    echo "  │ 广告结算     │ 每30分钟 │ php think ad:settle settle           │"
    echo "  │ 过期红包     │ 每小时   │ php think ad:settle expire           │"
    echo "  │ 分表预创建   │ 每月1号  │ php think split:create-tables        │"
    echo "  │ 数据归档     │ 每天3:30 │ php think data:migrate all           │"
    echo "  └──────────────┴──────────┴──────────────────────────────────────┘"
    echo ""
}

# ==================== 入口 ====================

case "${1:-help}" in
    start)
        do_start
        ;;
    stop)
        do_stop
        ;;
    restart)
        do_restart
        ;;
    status)
        do_status
        ;;
    help|--help|-h)
        show_help
        ;;
    *)
        log_error "未知命令: $1"
        show_help
        exit 1
        ;;
esac
