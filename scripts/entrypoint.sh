#!/bin/bash

# Store sessions in REDIS
cat > /layers/heroku_php/platform/etc/php/conf.d/sessions.ini <<EOF
session.save_handler = redis
session.save_path = "tcp://redis:6379?auth[]=${REDIS_PASSWORD}&database=2"

redis.session.locking_enabled = 1
redis.session.compression = zstd
EOF

# Launch apache/php-fpm
exec launcher heroku-php-apache2
