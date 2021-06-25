---
id: chevereto-3
title: Chevereto 3
sidebar_label: Chevereto 3
---

Author: Adapted from [Stefan Wieczorek](https://github.com/cloudpanel-io/docs/blob/master/cloudpanel-ce/docs/applications/wordpress-5.md) WordPress 5 guide

On this page, we explain step by step how to setup **[Chevereto 3](https://chevereto.com/)** with **CloudPanel**.

## Installation

In the following example we will setup **[Chevereto](https://chevereto.com/)** under the domain `www.domain.com`.

### Preparation

You will require to add **Chevereto as a CloudPanel** application.

1. Go to **Admin Area** and under **Vhost Templates** click on **Add Application**
   1. Application Name: Chevereto
   2. Status: Active
2. Go to the newly created application and click on **Add Vhost Template**
3. Template Name: Chevereto 3

Use following **Vhost Template** contents:

```nginx
server {
  listen 80;
  listen [::]:80;
  listen 443 ssl http2;
  listen [::]:443 ssl http2;
  {{ssl_certificate_key}}
  {{ssl_certificate}}
  {{server_name}}
  {{root}}

  {{nginx_access_log}}
  {{nginx_error_log}}

  if ($bad_bot = 1) {
    return 403;
  }

  if ($scheme != "https") {
    rewrite ^ https://$host$uri permanent;
  }

  location ~ /.well-known {
    auth_basic off;
    allow all;
  }

  {{basic_auth}}

  # Context limits
  client_max_body_size 50M;
  
  # Disable access to sensitive files
  location ~* (app|content|lib)/.*\.(po|php|lock|sql)$ {
      deny all;
  }
  
  # Image not found replacement
  location ~ \.(jpe?g|png|gif|webp)$ {
      log_not_found off;
      error_page 404 /content/images/system/default/404.gif;
  }
  
  # CORS header (avoids font rendering issues)
  location ~ \.(ttf|ttc|otf|eot|woff|woff2|font.css|css|js)$ {
      add_header Access-Control-Allow-Origin "*";
  }
  
  # Pretty URLs
  location / {
      index index.php;
      try_files $uri $uri/ /index.php$is_args$query_string;
  }

  location ~ \.php$ {
    include fastcgi_params;
    fastcgi_intercept_errors on;
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    try_files $uri =404;
    fastcgi_read_timeout 3600;
    fastcgi_send_timeout 3600;
    fastcgi_param HTTPS $fastcgi_https;
    {{php_fpm_listener}}
    {{php_settings}}
  }

  location ~* ^.+\.(css|js|jpg|jpeg|gif|png|ico|gz|svg|svgz|ttf|otf|woff|eot|mp4|ogg|ogv|webm|webp|zip|swf)$ {
    add_header Access-Control-Allow-Origin "*";
    expires max;
    access_log off;
  }

  if (-f $request_filename) {
    break;
  }
}
```

Before we can start with the installation, we need to create an [SSH User](https://github.com/cloudpanel-io/docs/tree/master/cloudpanel-ce/docs/frontend-area/users.md#adding-a-user), a [Database](https://github.com/cloudpanel-io/docs/tree/master/cloudpanel-ce/docs/frontend-area/databases.md#adding-a-database), and a [Domain](https://github.com/cloudpanel-io/docs/tree/master/cloudpanel-ce/docs/frontend-area/domains.md#adding-a-domain).

### Domain

When you [Add the Domain](https://github.com/cloudpanel-io/docs/tree/master/cloudpanel-ce/docs/frontend-area/domains.md#adding-a-domain), make sure to select the **Chevereto 3 Vhost Template** and the right **PHP Version (7.4)**.

### Vhost

After [Adding the Domain](https://github.com/cloudpanel-io/docs/tree/master/cloudpanel-ce/docs/frontend-area/domains.md#adding-a-domain), you can make changes on the [Vhost](https://github.com/cloudpanel-io/docs/tree/master/cloudpanel-ce/docs/frontend-area/domains.md#vhost) to your needs like adding application-specific rules.

### Chevereto Installation

[Login via SSH](https://github.com/cloudpanel-io/docs/tree/master/cloudpanel-ce/docs/frontend-area/users.md#ssh-login) then follow steps:

1. Go to the temp directory.

```sh
cd /tmp
```

2. Download and provide the **Chevereto Installer**.

```sh
curl -S -o installer.tar.gz -L "https://github.com/chevereto/installer/archive/2.2.3.tar.gz"
tar -xvzf installer.tar.gz
mv -v installer-2.2.3/installer.php /home/cloudpanel/htdocs/www.domain.com/
```

3. Reset permissions.

```sh
cd /home/cloudpanel/htdocs/
clpctl system:permissions:reset www.domain.com 775
```

4. Clean up the **tmp** directory.

```sh
rm -rf /tmp/*
```

5. Open your domain in the browser and go through the **Installation Wizard**.

## Cron Jobs

1. Go to **Cron Jobs** and click on **Add Cron Job**
2. Tweak the template to your liking (we recommend run every minute)
3. Take note on the command below

```sh
php7.4 /home/cloudpanel/htdocs/www.domain.com/cli.php -C cron
```
