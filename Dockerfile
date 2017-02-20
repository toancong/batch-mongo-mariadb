FROM toancong/phpup:centos-nginx-php7

RUN yum install -y php-devel pcre-devel gcc make openssl-devel
RUN pecl install mongodb
RUN echo "extension=mongodb.so" > /etc/php.d/mongodb.ini
