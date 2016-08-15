nano
====

## Requirements

```
sudo apt-get install php5-curl php5-imagick php5-json php5-mysqlnd php5-readline
```

### [MongoDB](http://php.net/manual/en/mongodb.installation.pecl.php)

> optional

```
sudo apt-get install php-pear php5-dev
sudo pecl install mongodb
```

## Configuration

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
