FROM php:8.1-fpm-alpine

LABEL maintainer="dev@advnet.com"
LABEL description="Supervisor for background tasks"

# 安装supervisor
RUN apk add --no-cache supervisor

# 创建日志目录
RUN mkdir -p /var/log/supervisor

# 复制配置文件
COPY supervisord.conf /etc/supervisord.conf
COPY conf.d /etc/supervisor/conf.d

# 启动supervisor
CMD ["supervisord", "-c", "/etc/supervisord.conf", "-n"]
