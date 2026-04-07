#!/bin/bash
# ============================================================================
# PHP + Nginx + MySQL 一键检测与配置脚本
# ============================================================================
# 用途: 在服务器上检测并自动安装配置 PHP、Nginx、MySQL 环境
# 适用系统: Ubuntu 20.04/22.04, Debian 10/11, CentOS 7/8
# 使用: chmod +x pnm.sh && sudo ./pnm.sh
# ============================================================================

set -e

# ==================== 配置区 ====================

# 项目域名（部署时修改）
DOMAIN="${DOMAIN:-advnet.example.com}"

# 项目根目录（默认为脚本所在目录）
PROJECT_DIR="$(cd "$(dirname "$0")" && pwd)"

# PHP 版本
PHP_VERSION="${PHP_VERSION:-8.1}"

# MySQL 配置
MYSQL_ROOT_PASSWORD="${MYSQL_ROOT_PASSWORD:-AdvNet@2026Secure}"
MYSQL_DATABASE="${MYSQL_DATABASE:-advnet}"
MYSQL_USER="${MYSQL_USER:-advnet}"
MYSQL_PASSWORD="${MYSQL_PASSWORD:-AdvNet@Db2026}"

# Nginx 配置
NGINX_PORT="${NGINX_PORT:-80}"
NGINX_SSL_PORT="${NGINX_SSL_PORT:-443}"

# 数据库表前缀
DB_PREFIX="${DB_PREFIX:-advn_}"

# 数据库字符集
DB_CHARSET="${DB_CHARSET:-utf8mb4}"

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

# ==================== 工具函数 ====================

print_banner() {
    echo -e "${CYAN}"
    echo "╔══════════════════════════════════════════════════════════════╗"
    echo "║       PHP + Nginx + MySQL 一键检测与配置脚本               ║"
    echo "║       适用于马上赚 (AdNetwork) 项目部署                    ║"
    echo "╚══════════════════════════════════════════════════════════════╝"
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

log_step() {
    echo ""
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${BLUE}  $1${NC}"
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
}

detect_os() {
    if [ -f /etc/os-release ]; then
        . /etc/os-release
        OS_ID=$ID
        OS_VERSION=$VERSION_ID
        OS_NAME=$PRETTY_NAME
    elif [ -f /etc/redhat-release ]; then
        OS_ID="centos"
        OS_VERSION=$(cat /etc/redhat-release | grep -oP '\d+' | head -1)
        OS_NAME=$(cat /etc/redhat-release)
    else
        OS_ID="unknown"
        OS_VERSION="unknown"
        OS_NAME="Unknown"
    fi
    log_info "检测到操作系统: $OS_NAME ($OS_ID $OS_VERSION)"
}

is_root() {
    if [ "$(id -u)" -ne 0 ]; then
        log_error "此脚本需要 root 权限运行"
        log_error "请使用: sudo $0"
        exit 1
    fi
}

command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# ==================== 系统更新 ====================

update_system() {
    log_step "1. 更新系统软件包"

    if [ "$OS_ID" = "ubuntu" ] || [ "$OS_ID" = "debian" ]; then
        log_info "执行 apt update..."
        apt update -y
        log_info "安装基础工具..."
        apt install -y curl wget git unzip software-properties-common \
            lsb-release gnupg2 ca-certificates apt-transport-https \
            language-pack-zh-hans locales
    elif [ "$OS_ID" = "centos" ] || [ "$OS_ID" = "rhel" ]; then
        log_info "执行 yum update..."
        yum update -y
        log_info "安装基础工具..."
        yum install -y curl wget git unzip epel-release \
            langpacks-zh_CN
    fi

    log_info "系统更新完成"
}

# ==================== Nginx 安装与配置 ====================

install_nginx() {
    log_step "2. 安装与配置 Nginx"

    if command_exists nginx; then
        NGINX_VERSION=$(nginx -v 2>&1)
        log_info "Nginx 已安装: $NGINX_VERSION"
    else
        log_info "正在安装 Nginx..."

        if [ "$OS_ID" = "ubuntu" ] || [ "$OS_ID" = "debian" ]; then
            apt install -y nginx
        elif [ "$OS_ID" = "centos" ] || [ "$OS_ID" = "rhel" ]; then
            yum install -y nginx
        fi

        log_info "Nginx 安装完成: $(nginx -v 2>&1)"
    fi

    # 创建 Nginx 配置
    log_info "生成 Nginx 站点配置..."

    cat > "/etc/nginx/sites-available/$DOMAIN.conf" << NGINX_EOF
# ============================================================================
# 马上赚 (AdNetwork) Nginx 配置
# 域名: ${DOMAIN}
# 项目目录: ${PROJECT_DIR}/public
# ============================================================================

server {
    listen ${NGINX_PORT};
    server_name ${DOMAIN};
    root ${PROJECT_DIR}/public;
    index index.php index.html index.htm;

    # 日志配置
    access_log /var/log/nginx/${DOMAIN}_access.log;
    error_log /var/log/nginx/${DOMAIN}_error.log;

    # 字符集
    charset utf-8;

    # 客户端上传限制
    client_max_body_size 50M;

    # 主路由规则 - ThinkPHP URL 重写
    location / {
        if (!-e \$request_filename) {
            rewrite ^(.*)\$ /index.php?s=\$1 last;
            break;
        }
    }

    # PHP-FPM 处理
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php${PHP_VERSION}-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;

        # 超时设置
        fastcgi_connect_timeout 300;
        fastcgi_send_timeout 300;
        fastcgi_read_timeout 300;
        fastcgi_buffer_size 64k;
        fastcgi_buffers 4 64k;
        fastcgi_busy_buffers_size 128k;
    }

    # 静态资源缓存
    location ~ .*\.(gif|jpg|jpeg|png|bmp|swf|ico|webp)\$ {
        expires 30d;
        access_log off;
    }

    location ~ .*\.(js|css)?\$ {
        expires 7d;
        access_log off;
    }

    # 禁止访问敏感文件
    location ~ /\. {
        deny all;
    }

    location ~ /(\.user\.ini|\.htaccess|\.git|\.env|\.svn) {
        deny all;
    }

    # 禁止访问 runtime 目录
    location ^~ /runtime/ {
        deny all;
    }

    # 禁止访问 application 目录
    location ^~ /application/ {
        deny all;
    }

    # deny 状态的 admin 模块需要通过 nginx 直接访问
    # 如需限制后台访问 IP，取消下面注释并修改 IP
    # location ^~ /admin {
    #     allow 你的IP;
    #     deny all;
    #     try_files \$uri /index.php?\$query_string;
    # }
}
NGINX_EOF

    # 创建符号链接
    ln -sf "/etc/nginx/sites-available/$DOMAIN.conf" "/etc/nginx/sites-enabled/$DOMAIN.conf"

    # 移除默认站点
    if [ -f /etc/nginx/sites-enabled/default ]; then
        rm -f /etc/nginx/sites-enabled/default
    fi

    # 检测 Nginx 配置
    nginx -t
    if [ $? -eq 0 ]; then
        log_info "Nginx 配置检查通过"
    else
        log_error "Nginx 配置检查失败，请检查配置文件"
        exit 1
    fi
}

# ==================== MySQL 安装与配置 ====================

install_mysql() {
    log_step "3. 安装与配置 MySQL"

    if command_exists mysql; then
        MYSQL_VER=$(mysql --version)
        log_info "MySQL 已安装: $MYSQL_VER"
    else
        log_info "正在安装 MySQL..."

        if [ "$OS_ID" = "ubuntu" ] || [ "$OS_ID" = "debian" ]; then
            # Ubuntu 22.04+ 使用 mysql 包
            if [ "$OS_ID" = "ubuntu" ] && [ "$(echo "$OS_VERSION >= 22.04" | bc)" -eq 1 ]; then
                debconf-set-selections <<< "mysql-server mysql-server/root_password password $MYSQL_ROOT_PASSWORD"
                debconf-set-selections <<< "mysql-server mysql-server/root_password_again password $MYSQL_ROOT_PASSWORD"
                apt install -y mysql-server
            else
                apt install -y mysql-server
            fi
        elif [ "$OS_ID" = "centos" ] || [ "$OS_ID" = "rhel" ]; then
            yum install -y mysql-server
        fi

        log_info "MySQL 安装完成"
    fi

    # 启动 MySQL
    log_info "确保 MySQL 服务运行中..."
    if [ "$OS_ID" = "ubuntu" ] || [ "$OS_ID" = "debian" ]; then
        service mysql start 2>/dev/null || systemctl start mysql 2>/dev/null || true
        systemctl enable mysql 2>/dev/null || update-rc.d mysql defaults 2>/dev/null || true
    elif [ "$OS_ID" = "centos" ] || [ "$OS_ID" = "rhel" ]; then
        service mysqld start 2>/dev/null || systemctl start mysqld 2>/dev/null || true
        systemctl enable mysqld 2>/dev/null || chkconfig mysqld on 2>/dev/null || true
    fi

    # 检查 MySQL 是否运行
    sleep 2
    if mysqladmin ping -u root --silent 2>/dev/null; then
        log_info "MySQL 服务运行正常"
    else
        log_warn "MySQL 可能需要手动设置 root 密码"
        log_warn "请执行: mysql_secure_installation"
    fi

    # 配置 MySQL
    log_info "配置 MySQL..."

    # 创建数据库和用户
    mysql -u root << SQL_EOF
-- 创建数据库
CREATE DATABASE IF NOT EXISTS \`${MYSQL_DATABASE}\` CHARACTER SET ${DB_CHARSET} COLLATE ${DB_CHARSET}_unicode_ci;

-- 创建用户并授权（如果不存在）
CREATE USER IF NOT EXISTS '${MYSQL_USER}'@'localhost' IDENTIFIED BY '${MYSQL_PASSWORD}';
CREATE USER IF NOT EXISTS '${MYSQL_USER}'@'127.0.0.1' IDENTIFIED BY '${MYSQL_PASSWORD}';

-- 授权
GRANT ALL PRIVILEGES ON \`${MYSQL_DATABASE}\`.* TO '${MYSQL_USER}'@'localhost';
GRANT ALL PRIVILEGES ON \`${MYSQL_DATABASE}\`.* TO '${MYSQL_USER}'@'127.0.0.1';
FLUSH PRIVILEGES;

-- 显示数据库信息
SELECT 'Database created: ${MYSQL_DATABASE}' AS status;
SELECT 'User created: ${MYSQL_USER}@localhost' AS status;
SQL_EOF

    if [ $? -eq 0 ]; then
        log_info "MySQL 数据库和用户创建成功"
    else
        log_warn "MySQL 配置可能需要手动调整"
        log_warn "请手动执行 SQL 创建数据库和用户"
    fi

    # MySQL 优化配置
    log_info "生成 MySQL 优化配置..."
    local my_cnf="/etc/mysql/conf.d/advnet.cnf"
    if [ ! -f "$my_cnf" ]; then
        mkdir -p /etc/mysql/conf.d 2>/dev/null || true
        cat > "$my_cnf" << MYSQL_CNF_EOF
[mysqld]
# 马上赚 (AdNetwork) MySQL 优化配置

# 字符集
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci

# 连接数
max_connections = 500
max_connect_errors = 1000

# 缓冲区
innodb_buffer_pool_size = 256M
innodb_log_file_size = 64M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT

# 查询缓存 (MySQL 8.0 已移除)
# query_cache_size = 64M
# query_cache_type = 1

# 临时表
tmp_table_size = 64M
max_heap_table_size = 64M

# 超时
wait_timeout = 600
interactive_timeout = 600
connect_timeout = 10

# 日志
slow_query_log = 1
slow_query_log_file = /var/log/mysql/mysql-slow.log
long_query_time = 2

[client]
default-character-set = utf8mb4

[mysql]
default-character-set = utf8mb4
MYSQL_CNF_EOF
        log_info "MySQL 优化配置已写入: $my_cnf"
    fi
}

# ==================== PHP 安装与配置 ====================

install_php() {
    log_step "4. 安装与配置 PHP $PHP_VERSION"

    if command_exists php; then
        CURRENT_PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
        log_info "PHP 已安装: $(php -v | head -1)"

        if [ "$CURRENT_PHP_VERSION" != "$PHP_VERSION" ]; then
            log_warn "当前 PHP 版本为 $CURRENT_PHP_VERSION，目标版本为 $PHP_VERSION"
            log_warn "建议安装正确版本的 PHP"
        fi
    else
        log_info "正在安装 PHP $PHP_VERSION..."

        if [ "$OS_ID" = "ubuntu" ] || [ "$OS_ID" = "debian" ]; then
            # 添加 PPA
            add-apt-repository -y ppa:ondrej/php
            apt update -y

            # 安装 PHP-FPM 及扩展
            apt install -y \
                php${PHP_VERSION}-fpm \
                php${PHP_VERSION}-mysql \
                php${PHP_VERSION}-gd \
                php${PHP_VERSION}-mbstring \
                php${PHP_VERSION}-xml \
                php${PHP_VERSION}-curl \
                php${PHP_VERSION}-zip \
                php${PHP_VERSION}-bcmath \
                php${PHP_VERSION}-json \
                php${PHP_VERSION}-redis \
                php${PHP_VERSION}-opcache \
                php${PHP_VERSION}-intl

        elif [ "$OS_ID" = "centos" ] || [ "$OS_ID" = "rhel" ]; then
            # CentOS 需要 Remi 仓库
            if [ "$OS_VERSION" = "7" ]; then
                yum install -y https://rpms.remirepo.net/enterprise/remi-release-7.rpm
                yum install -y yum-utils
                yum-config-manager --enable remi-php81
            elif [ "$OS_VERSION" = "8" ]; then
                dnf install -y https://rpms.remirepo.net/enterprise/remi-release-8.rpm
                dnf install -y dnf-utils
                dnf module enable -y php:remi-8.1
            fi

            yum install -y \
                php-fpm \
                php-mysqlnd \
                php-gd \
                php-mbstring \
                php-xml \
                php-curl \
                php-zip \
                php-bcmath \
                php-json \
                php-pecl-redis5 \
                php-opcache \
                php-intl
        fi

        log_info "PHP $PHP_VERSION 安装完成"
    fi

    # 检查 PHP 扩展
    log_info "检查 PHP 扩展..."
    REQUIRED_EXTENSIONS="json curl pdo pdo_mysql mbstring openssl bcmath"
    OPTIONAL_EXTENSIONS="redis gd zip xml opcache intl"

    for ext in $REQUIRED_EXTENSIONS; do
        if php -m 2>/dev/null | grep -qi "^${ext}$"; then
            log_info "  [✓] $ext (必需)"
        else
            log_error "  [✗] $ext (必需) - 未安装！"
        fi
    done

    for ext in $OPTIONAL_EXTENSIONS; do
        if php -m 2>/dev/null | grep -qi "^${ext}$"; then
            log_info "  [✓] $ext (可选)"
        else
            log_warn "  [✗] $ext (可选) - 建议安装"
        fi
    done

    # 配置 PHP-FPM
    log_info "配置 PHP-FPM..."
    local php_fpm_conf="/etc/php/${PHP_VERSION}/fpm/pool.d/www.conf"

    if [ -f "$php_fpm_conf" ]; then
        # 优化 PHP-FPM 配置
        sed -i 's/^pm.max_children = .*/pm.max_children = 50/' "$php_fpm_conf" 2>/dev/null || true
        sed -i 's/^pm.start_servers = .*/pm.start_servers = 5/' "$php_fpm_conf" 2>/dev/null || true
        sed -i 's/^pm.min_spare_servers = .*/pm.min_spare_servers = 3/' "$php_fpm_conf" 2>/dev/null || true
        sed -i 's/^pm.max_spare_servers = .*/pm.max_spare_servers = 10/' "$php_fpm_conf" 2>/dev/null || true
        sed -i 's/^;request_terminate_timeout = .*/request_terminate_timeout = 300/' "$php_fpm_conf" 2>/dev/null || true
        sed -i 's/^pm.max_requests = .*/pm.max_requests = 1000/' "$php_fpm_conf" 2>/dev/null || true

        log_info "PHP-FPM 配置已优化"
    fi

    # 配置 php.ini
    log_info "配置 php.ini..."
    local php_ini="/etc/php/${PHP_VERSION}/fpm/php.ini"

    if [ -f "$php_ini" ]; then
        # 设置时区
        sed -i 's/^date.timezone =.*/date.timezone = Asia\/Shanghai/' "$php_ini" 2>/dev/null || true
        grep -q "^date.timezone" "$php_ini" 2>/dev/null || echo "date.timezone = Asia/Shanghai" >> "$php_ini"

        # 上传大小限制
        sed -i 's/^upload_max_filesize =.*/upload_max_filesize = 50M/' "$php_ini" 2>/dev/null || true
        sed -i 's/^post_max_size =.*/post_max_size = 50M/' "$php_ini" 2>/dev/null || true
        sed -i 's/^max_execution_time =.*/max_execution_time = 300/' "$php_ini" 2>/dev/null || true
        sed -i 's/^memory_limit =.*/memory_limit = 256M/' "$php_ini" 2>/dev/null || true

        # OPcache 配置
        sed -i 's/^opcache.enable =.*/opcache.enable = 1/' "$php_ini" 2>/dev/null || true
        sed -i 's/^opcache.memory_consumption =.*/opcache.memory_consumption = 128/' "$php_ini" 2>/dev/null || true
        sed -i 's/^opcache.max_accelerated_files =.*/opcache.max_accelerated_files = 4000/' "$php_ini" 2>/dev/null || true

        log_info "php.ini 配置已优化"
    fi

    # 重启 PHP-FPM
    log_info "重启 PHP-FPM..."
    service php${PHP_VERSION}-fpm restart 2>/dev/null || systemctl restart php${PHP_VERSION}-fpm 2>/dev/null || true
    log_info "PHP-FPM 重启完成"
}

# ==================== Redis 安装 ====================

install_redis() {
    log_step "5. 安装与配置 Redis"

    if command_exists redis-server || command_exists redis-cli; then
        log_info "Redis 已安装"
    else
        log_info "正在安装 Redis..."

        if [ "$OS_ID" = "ubuntu" ] || [ "$OS_ID" = "debian" ]; then
            apt install -y redis-server
        elif [ "$OS_ID" = "centos" ] || [ "$OS_ID" = "rhel" ]; then
            yum install -y redis
        fi

        log_info "Redis 安装完成"
    fi

    # 确保 Redis 运行
    log_info "确保 Redis 服务运行中..."
    if [ "$OS_ID" = "ubuntu" ] || [ "$OS_ID" = "debian" ]; then
        service redis-server start 2>/dev/null || systemctl start redis-server 2>/dev/null || true
        systemctl enable redis-server 2>/dev/null || update-rc.d redis-server defaults 2>/dev/null || true
    elif [ "$OS_ID" = "centos" ] || [ "$OS_ID" = "rhel" ]; then
        service redis start 2>/dev/null || systemctl start redis 2>/dev/null || true
        systemctl enable redis 2>/dev/null || chkconfig redis on 2>/dev/null || true
    fi

    sleep 1
    if redis-cli ping 2>/dev/null | grep -q "PONG"; then
        log_info "Redis 服务运行正常"
    else
        log_warn "Redis 服务可能未正常运行"
    fi
}

# ==================== Composer 安装 ====================

install_composer() {
    log_step "6. 安装 Composer"

    if command_exists composer; then
        log_info "Composer 已安装: $(composer --version)"
    else
        log_info "正在安装 Composer..."
        curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
        log_info "Composer 安装完成: $(composer --version)"
    fi

    # 安装项目 PHP 依赖
    if [ -f "$PROJECT_DIR/composer.json" ]; then
        log_info "安装项目 PHP 依赖..."
        cd "$PROJECT_DIR"
        composer install --no-dev --optimize-autoloader 2>/dev/null || composer install 2>/dev/null
        log_info "项目 PHP 依赖安装完成"
    fi
}

# ==================== 项目配置 ====================

configure_project() {
    log_step "7. 配置项目"

    # 修复目录权限
    log_info "设置目录权限..."
    chown -R www-data:www-data "$PROJECT_DIR" 2>/dev/null || chown -R nginx:nginx "$PROJECT_DIR" 2>/dev/null || true
    chmod -R 755 "$PROJECT_DIR"
    chmod -R 777 "$PROJECT_DIR/runtime"
    if [ -d "$PROJECT_DIR/public/uploads" ]; then
        chmod -R 777 "$PROJECT_DIR/public/uploads"
    fi
    log_info "目录权限设置完成"

    # 生成 .env 文件
    log_info "生成环境配置文件..."
    if [ ! -f "$PROJECT_DIR/.env" ]; then
        cat > "$PROJECT_DIR/.env" << ENV_EOF
# ============================================================================
# 马上赚 (AdNetwork) 环境配置
# 自动生成于 $(date '+%Y-%m-%d %H:%M:%S')
# ============================================================================

[APP]
debug = false
trace = false

[DATABASE]
type = mysql
hostname = 127.0.0.1
database = ${MYSQL_DATABASE}
username = ${MYSQL_USER}
password = ${MYSQL_PASSWORD}
hostport = 3306
charset = ${DB_CHARSET}
prefix = ${DB_PREFIX}
debug = false
ENV_EOF
        log_info ".env 文件已生成"
    else
        log_info ".env 文件已存在，跳过"
    fi

    # 导入数据库
    if [ -f "$PROJECT_DIR/sql/advnet.sql" ]; then
        log_info "检测到数据库 SQL 文件，是否导入？(y/n)"
        read -r -p "导入数据库? [y/N] " import_db
        if [[ "$import_db" =~ ^[Yy]$ ]]; then
            log_info "正在导入数据库..."
            mysql -u "$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" < "$PROJECT_DIR/sql/advnet.sql"
            if [ $? -eq 0 ]; then
                log_info "数据库导入成功"
            else
                log_error "数据库导入失败，请检查 SQL 文件和数据库连接"
            fi
        else
            log_info "跳过数据库导入"
        fi
    else
        log_warn "未找到数据库 SQL 文件: $PROJECT_DIR/sql/advnet.sql"
    fi
}

# ==================== 服务重启 ====================

restart_services() {
    log_step "8. 重启所有服务"

    log_info "重启 PHP-FPM..."
    service php${PHP_VERSION}-fpm restart 2>/dev/null || systemctl restart php${PHP_VERSION}-fpm 2>/dev/null || true

    log_info "重启 Nginx..."
    service nginx restart 2>/dev/null || systemctl restart nginx 2>/dev/null || true

    log_info "重启 Redis..."
    service redis-server restart 2>/dev/null || systemctl restart redis-server 2>/dev/null || true

    log_info "所有服务重启完成"
}

# ==================== 环境检测报告 ====================

check_environment() {
    log_step "环境检测报告"

    echo -e "  ┌──────────────────────┬──────────────────────────────────┐"
    echo -e "  │ 项目                 │ 状态                             │"
    echo -e "  ├──────────────────────┼──────────────────────────────────┤"

    # 操作系统
    printf "  │ %-20s │ %-32s │\n" "操作系统" "$OS_NAME"
    echo -e "  ├──────────────────────┼──────────────────────────────────┤"

    # Nginx
    if command_exists nginx; then
        printf "  │ %-20s │ ${GREEN}%-32s${NC} │\n" "Nginx" "$(nginx -v 2>&1 | cut -d/ -f2)"
    else
        printf "  │ %-20s │ ${RED}%-32s${NC} │\n" "Nginx" "未安装"
    fi
    echo -e "  ├──────────────────────┼──────────────────────────────────┤"

    # PHP
    if command_exists php; then
        printf "  │ %-20s │ ${GREEN}%-32s${NC} │\n" "PHP" "$(php -v | head -1)"
    else
        printf "  │ %-20s │ ${RED}%-32s${NC} │\n" "PHP" "未安装"
    fi
    echo -e "  ├──────────────────────┼──────────────────────────────────┤"

    # PHP-FPM
    if [ -S "/var/run/php/php${PHP_VERSION}-fpm.sock" ]; then
        printf "  │ %-20s │ ${GREEN}%-32s${NC} │\n" "PHP-FPM" "运行中 (php${PHP_VERSION}-fpm)"
    else
        printf "  │ %-20s │ ${RED}%-32s${NC} │\n" "PHP-FPM" "未运行"
    fi
    echo -e "  ├──────────────────────┼──────────────────────────────────┤"

    # MySQL
    if command_exists mysql; then
        printf "  │ %-20s │ ${GREEN}%-32s${NC} │\n" "MySQL" "$(mysql --version | awk '{print $5}' | cut -d, -f1)"
    else
        printf "  │ %-20s │ ${RED}%-32s${NC} │\n" "MySQL" "未安装"
    fi
    echo -e "  ├──────────────────────┼──────────────────────────────────┤"

    # Redis
    if command_exists redis-cli && redis-cli ping 2>/dev/null | grep -q "PONG"; then
        printf "  │ %-20s │ ${GREEN}%-32s${NC} │\n" "Redis" "运行中"
    else
        printf "  │ %-20s │ ${RED}%-32s${NC} │\n" "Redis" "未运行"
    fi
    echo -e "  ├──────────────────────┼──────────────────────────────────┤"

    # Composer
    if command_exists composer; then
        printf "  │ %-20s │ ${GREEN}%-32s${NC} │\n" "Composer" "$(composer --version 2>/dev/null | head -1)"
    else
        printf "  │ %-20s │ ${RED}%-32s${NC} │\n" "Composer" "未安装"
    fi
    echo -e "  ├──────────────────────┼──────────────────────────────────┤"

    # 项目目录
    if [ -f "$PROJECT_DIR/think" ]; then
        printf "  │ %-20s │ ${GREEN}%-32s${NC} │\n" "项目目录" "$PROJECT_DIR"
    else
        printf "  │ %-20s │ ${RED}%-32s${NC} │\n" "项目目录" "未找到 ThinkPHP"
    fi
    echo -e "  ├──────────────────────┼──────────────────────────────────┤"

    # runtime 权限
    if [ -w "$PROJECT_DIR/runtime" ]; then
        printf "  │ %-20s │ ${GREEN}%-32s${NC} │\n" "Runtime 权限" "可写"
    else
        printf "  │ %-20s │ ${RED}%-32s${NC} │\n" "Runtime 权限" "不可写"
    fi

    echo -e "  └──────────────────────┴──────────────────────────────────┘"
}

# ==================== 使用说明 ====================

show_usage() {
    echo ""
    echo "配置完成后，请按以下步骤操作:"
    echo ""
    echo -e "  ${CYAN}1. 配置域名解析${NC}"
    echo "     将域名 $DOMAIN 的 A 记录指向服务器 IP"
    echo ""
    echo -e "  ${CYAN}2. (可选) 配置 SSL 证书${NC}"
    echo "     sudo apt install certbot python3-certbot-nginx  # Ubuntu/Debian"
    echo "     sudo certbot --nginx -d $DOMAIN"
    echo ""
    echo -e "  ${CYAN}3. 配置后台管理${NC}"
    echo "     访问 http://$DOMAIN/admin 进入后台管理"
    echo "     默认管理员账号: admin / 密码在数据库 fa_admin 表中查看"
    echo ""
    echo -e "  ${CYAN}4. 配置微信登录${NC}"
    echo "     在后台管理 → 系统配置 → 第三方登录 中配置微信 AppID 和 AppSecret"
    echo ""
    echo -e "  ${CYAN}5. 启动定时任务${NC}"
    echo "     cd $PROJECT_DIR && ./server.sh start"
    echo ""
    echo -e "  ${CYAN}6. (可选) 启动 WebSocket 服务${NC}"
    echo "     php $PROJECT_DIR/think websocket start -d"
    echo ""
    echo "常用管理命令:"
    echo "  sudo systemctl status nginx       # 查看 Nginx 状态"
    echo "  sudo systemctl status php${PHP_VERSION}-fpm  # 查看 PHP-FPM 状态"
    echo "  sudo systemctl status mysql       # 查看 MySQL 状态"
    echo "  sudo systemctl status redis-server # 查看 Redis 状态"
    echo "  sudo tail -f /var/log/nginx/${DOMAIN}_error.log  # Nginx 错误日志"
    echo "  sudo tail -f /var/log/mysql/mysql-slow.log      # MySQL 慢查询日志"
    echo ""
}

# ==================== 主流程 ====================

main() {
    print_banner
    is_root
    detect_os

    echo ""
    echo -e "${YELLOW}配置信息:${NC}"
    echo "  域名:     $DOMAIN"
    echo "  项目目录: $PROJECT_DIR"
    echo "  PHP版本:  $PHP_VERSION"
    echo "  数据库:   $MYSQL_DATABASE"
    echo "  数据库用户: $MYSQL_USER"
    echo "  表前缀:   $DB_PREFIX"
    echo ""

    # 确认执行
    read -r -p "确认开始安装配置? [y/N] " confirm
    if [[ ! "$confirm" =~ ^[Yy]$ ]]; then
        log_info "已取消"
        exit 0
    fi

    # 执行安装步骤
    update_system
    install_nginx
    install_mysql
    install_redis
    install_php
    install_composer
    configure_project
    restart_services

    # 最终报告
    check_environment
    show_usage

    echo ""
    log_info "========== 配置完成 =========="
    echo ""
    echo -e "  ${GREEN}网站地址:${NC}  http://$DOMAIN"
    echo -e "  ${GREEN}后台管理:${NC}  http://$DOMAIN/admin"
    echo -e "  ${GREEN}API接口:${NC}   http://$DOMAIN/api"
    echo ""
}

# 只检查环境
check_only() {
    print_banner
    detect_os
    check_environment
}

# 帮助信息
show_help() {
    print_banner
    echo ""
    echo "用法: sudo $0 [命令]"
    echo ""
    echo "命令:"
    echo "  (无参数)   完整安装配置（Nginx + PHP + MySQL + Redis + Composer + 项目配置）"
    echo "  check      仅检查当前环境状态"
    echo "  help       显示帮助信息"
    echo ""
    echo "环境变量:"
    echo "  DOMAIN              项目域名 (默认: advnet.example.com)"
    echo "  PHP_VERSION         PHP 版本 (默认: 8.1)"
    echo "  MYSQL_ROOT_PASSWORD MySQL root 密码"
    echo "  MYSQL_DATABASE      数据库名 (默认: advnet)"
    echo "  MYSQL_USER          数据库用户 (默认: advnet)"
    echo "  MYSQL_PASSWORD      数据库密码"
    echo "  DB_PREFIX           数据库表前缀 (默认: advn_)"
    echo ""
    echo "示例:"
    echo "  sudo DOMAIN=example.com MYSQL_PASSWORD=MyPass123 $0"
    echo "  sudo PHP_VERSION=7.4 $0"
    echo ""
}

# ==================== 入口 ====================

case "${1:-}" in
    check)
        check_only
        ;;
    help|--help|-h)
        show_help
        ;;
    *)
        main
        ;;
esac
