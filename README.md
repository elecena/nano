nano
[![Latest Stable Version](http://poser.pugx.org/elecena/nano/v)](https://packagist.org/packages/elecena/nano)
[![phpunit](https://github.com/elecena/nano/actions/workflows/tests.yaml/badge.svg)](https://github.com/elecena/nano/actions/workflows/tests.yaml)
[![Coverage Status](https://coveralls.io/repos/github/elecena/nano/badge.svg?branch=master)](https://coveralls.io/github/elecena/nano?branch=master)
====

## Testing

```
docker run -d -p 6379:6379 --name redis-test redis:5.0.9-alpine redis-server --requirepass qwerty --port 6379
docker run -d -p 5555:80 --name httpin kennethreitz/httpbin
composer run test
```

## Configuration

* Add the following entry to `/etc/hosts`:

```
127.0.0.1       elecena.local
```

* Copy `../app/apache/000-elecena.conf` to `/etc/apache2/sites-available`.
* `sudo a2enmod rewrite && sudo a2enmod php5 && sudo a2ensite 000-elecena.conf`
* `sudo service apache2 restart`

### rsyslog

* Uncomment the following in `/etc/rsyslog.conf`:

```
# provides UDP syslog reception
$ModLoad imudp
$UDPServerRun 514
```

* `sudo service rsyslog restart`

## Apache

```
RewriteEngine On

# static assets
RewriteRule \.(css|js|gif|png|jpg)$ static.php [L]

# API requests
RewriteRule \.(json|xml)$ api.php [L]

# URL - last (always redirect)
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d 
RewriteRule (.*) index.php [L]
```

## Nginx

```
<nginx>
	<location path="/">
		<!-- static assets -->
		<rewrite>\.(css|js|gif|png|jpg)$ /static.php</rewrite>

		<!-- API requests -->
		<rewrite>\.(json|xml)$ /api.php</rewrite>

		<!-- URL - last (always redirect) -->
		<!-- @see http://rootnode.net/web#vhost-configuration-advanced-mode -->
		<if condition="!-f $request_filename">
			<rewrite>^/(.+)$ /index.php last</rewrite>
			<break/>
		</if>
	</location>
</nginx>
```
