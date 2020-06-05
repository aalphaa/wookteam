# Install

**[中文文档](./INSTALL.md)**

## Setup

> Fork the repo if you are outside collaborator https://github.com/kuaifan/wookteam.

#### Clone the project to your local or server

```bash
// using ssh
$ git clone git@github.com:kuaifan/wookteam.git
// or you can use https
$ git clone https://github.com/kuaifan/wookteam.git
```

#### Configure remotes

```bash
$ cd wookteam
$ git remote add origin git@github.com:kuaifan/wookteam.git
```

#### Copy`.env`

```bash
$ cp .env.example .env
```

#### Modify`.env`

database、websocket

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wookteam
DB_USERNAME=root
DB_PASSWORD=123456

......

LARAVELS_LISTEN_IP=127.0.0.1
LARAVELS_LISTEN_PORT=5200
LARAVELS_PROXY_URL=ws://wookteam.com/ws
```

#### Setup application

```bash
$ git checkout master # use dev branch for local development
$ git pull origin master # use dev branch for local development
$ composer install
$ php artisan key:generate
$ php artisan migrate --seed
$ npm install
$ npm run production
```

#### Run Laravels (websocket)

```bash
$ php bin/laravels start
```

> It is recommended to supervise the main process through [Supervisord](http://supervisord.org/), the premise is without option `-d` and to set `swoole.daemonize` to `false`.

```
[program:wookteam-test]
directory=/srv/wookteam.com
command=/usr/local/bin/php bin/laravels start -i
numprocs=1
autostart=true
autorestart=true
startretries=3
user=www
redirect_stderr=true
stdout_logfile=/var/log/supervisor/%(program_name)s.log
```

## Deployment To Nginx

```nginx
map $http_upgrade $connection_upgrade {
    default upgrade;
    ''      close;
}
upstream swoole {
    # Connect IP:Port
    server 127.0.0.1:5200 weight=5 max_fails=3 fail_timeout=30s;
    keepalive 16;
}
server {
    listen 80;
    
    # Don't forget to bind the host
    server_name wookteam.com;
    root /srv/wookteam.com/public;

    autoindex off;
    index index.html index.htm index.php;

    charset utf-8;

    location / {
        try_files $uri @laravels;
    }

    location =/ws {
        proxy_http_version 1.1;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Real-PORT $remote_port;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header Host $http_host;
        proxy_set_header Scheme $scheme;
        proxy_set_header Server-Protocol $server_protocol;
        proxy_set_header Server-Name $server_name;
        proxy_set_header Server-Addr $server_addr;
        proxy_set_header Server-Port $server_port;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection $connection_upgrade;
        # "swoole" is the upstream
        proxy_pass http://swoole;
    }

    location @laravels {
        proxy_http_version 1.1;
        proxy_set_header Connection "";
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Real-PORT $remote_port;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header Host $http_host;
        proxy_set_header Scheme $scheme;
        proxy_set_header Server-Protocol $server_protocol;
        proxy_set_header Server-Name $server_name;
        proxy_set_header Server-Addr $server_addr;
        proxy_set_header Server-Port $server_port;
        # "swoole" is the upstream
        proxy_pass http://swoole;
    }
}
```
