#!/bin/bash

# ===========================================
# 短视频金币平台 - 部署脚本
# ===========================================

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 日志函数
log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# 检查Docker
check_docker() {
    if ! command -v docker &> /dev/null; then
        log_error "Docker未安装，请先安装Docker"
        exit 1
    fi

    if ! command -v docker-compose &> /dev/null; then
        log_error "Docker Compose未安装，请先安装"
        exit 1
    fi

    log_info "Docker环境检查通过"
}

# 创建必要的目录
create_directories() {
    log_info "创建必要的目录..."
    
    mkdir -p nginx/logs
    mkdir -p nginx/ssl
    mkdir -p php/logs
    mkdir -p mysql/backup
    mkdir -p supervisor/logs
    
    log_info "目录创建完成"
}

# 检查环境变量
check_env() {
    if [ ! -f ".env" ]; then
        log_warn ".env文件不存在，从模板创建..."
        cp .env.example .env
        log_warn "请编辑.env文件配置正确的密码和密钥"
        exit 1
    fi
    
    log_info "环境变量配置检查通过"
}

# 生成SSL证书（自签名，生产环境请使用正式证书）
generate_ssl() {
    if [ ! -f "nginx/ssl/api.advnet.com.crt" ]; then
        log_warn "生成自签名SSL证书（仅用于测试）..."
        
        openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
            -keyout nginx/ssl/api.advnet.com.key \
            -out nginx/ssl/api.advnet.com.crt \
            -subj "/C=CN/ST=Shanghai/L=Shanghai/O=AdvNet/OU=IT/CN=api.advnet.com"
        
        log_info "SSL证书生成完成"
    fi
}

# 拉取镜像
pull_images() {
    log_info "拉取Docker镜像..."
    docker-compose pull
    log_info "镜像拉取完成"
}

# 构建镜像
build_images() {
    log_info "构建自定义镜像..."
    docker-compose build
    log_info "镜像构建完成"
}

# 启动服务
start_services() {
    log_info "启动服务..."
    docker-compose up -d
    log_info "服务启动完成"
}

# 等待服务就绪
wait_for_services() {
    log_info "等待服务就绪..."
    
    # 等待MySQL
    log_info "等待MySQL..."
    until docker-compose exec -T mysql mysqladmin ping -h localhost -uroot -p${MYSQL_ROOT_PASSWORD} --silent; do
        sleep 2
    done
    log_info "MySQL就绪"
    
    # 等待Redis
    log_info "等待Redis..."
    until docker-compose exec -T redis redis-cli -a ${REDIS_PASSWORD} ping | grep -q PONG; do
        sleep 2
    done
    log_info "Redis就绪"
    
    # 等待RabbitMQ
    log_info "等待RabbitMQ..."
    until docker-compose exec -T rabbitmq rabbitmqctl status > /dev/null 2>&1; do
        sleep 2
    done
    log_info "RabbitMQ就绪"
}

# 初始化数据库
init_database() {
    log_info "初始化数据库..."
    
    # 导入SQL文件
    for sql_file in ../sql/*.sql; do
        if [ -f "$sql_file" ]; then
            log_info "导入 $sql_file ..."
            docker-compose exec -T mysql mysql -uadvnet -p${DB_PASSWORD} advnet < "$sql_file"
        fi
    done
    
    log_info "数据库初始化完成"
}

# 安装Composer依赖
install_dependencies() {
    log_info "安装PHP依赖..."
    docker-compose exec -T php-fpm composer install --no-dev --optimize-autoloader
    log_info "依赖安装完成"
}

# 清理缓存
clear_cache() {
    log_info "清理应用缓存..."
    docker-compose exec -T php-fpm php think clear
    log_info "缓存清理完成"
}

# 检查服务状态
check_status() {
    log_info "服务状态:"
    docker-compose ps
}

# 显示访问信息
show_info() {
    echo ""
    echo "=========================================="
    echo "部署完成！"
    echo "=========================================="
    echo ""
    echo "API地址: https://api.advnet.com"
    echo "后台地址: https://admin.advnet.com"
    echo "RabbitMQ管理: http://localhost:15672"
    echo "Kibana日志: http://localhost:5601"
    echo ""
    echo "常用命令:"
    echo "  查看日志: docker-compose logs -f [服务名]"
    echo "  重启服务: docker-compose restart [服务名]"
    echo "  停止服务: docker-compose stop"
    echo "  启动服务: docker-compose start"
    echo ""
}

# 主函数
main() {
    case "$1" in
        install)
            check_docker
            create_directories
            check_env
            generate_ssl
            pull_images
            build_images
            start_services
            wait_for_services
            init_database
            install_dependencies
            clear_cache
            check_status
            show_info
            ;;
        start)
            start_services
            check_status
            ;;
        stop)
            docker-compose stop
            log_info "服务已停止"
            ;;
        restart)
            docker-compose restart
            check_status
            ;;
        rebuild)
            build_images
            docker-compose up -d
            check_status
            ;;
        logs)
            docker-compose logs -f ${2:-}
            ;;
        status)
            check_status
            ;;
        *)
            echo "用法: $0 {install|start|stop|restart|rebuild|logs|status}"
            echo ""
            echo "命令说明:"
            echo "  install  - 完整安装部署"
            echo "  start    - 启动服务"
            echo "  stop     - 停止服务"
            echo "  restart  - 重启服务"
            echo "  rebuild  - 重新构建并启动"
            echo "  logs     - 查看日志 (可指定服务名)"
            echo "  status   - 查看服务状态"
            exit 1
            ;;
    esac
}

main "$@"
