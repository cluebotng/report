#!/usr/bin/bash
set -e
#
# Run within
# `podman run -v $(pwd):/mnt -ti -p 127.0.0.1:8081:80 ubuntu:latest`
#
export DEBIAN_FRONTEND=noninteractive

# Install PHP 7.2
apt-get update
apt-get install -y gnupg git unzip

echo 'deb http://ppa.launchpad.net/ondrej/php/ubuntu focal main' > /etc/apt/sources.list.d/php72.list
echo 'deb-src http://ppa.launchpad.net/ondrej/php/ubuntu focal main' >> /etc/apt/sources.list.d/php72.list
apt-key adv --keyserver keyserver.ubuntu.com --recv-keys 4F4EA0AAE5267A6C

apt-get clean
apt-get update

apt-get install -y php8.3-cli php8.3-xml php8.3-curl php8.3-zip php8.3-fpm php8.3-mysql

# Setup FPM
apt-get install -y nginx

# Setup nginx
cat > /etc/nginx/sites-available/default <<'EOF'
server {
    listen         80 default_server;
    server_name    report;
    root           /mnt;
    index          index.php;

    location ~* \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        include         fastcgi_params;
        fastcgi_param   SCRIPT_FILENAME    $document_root$fastcgi_script_name;
        fastcgi_param   SCRIPT_NAME        $fastcgi_script_name;
    }
}
EOF

# Start the 2 deamons
php-fpm8.3
pgrep nginx && (killall -HUP nginx) || (nginx)
