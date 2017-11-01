nano
====

## Requirements

```
sudo apt-get install php5-curl php5-imagick php5-json php5-mysqlnd php5-readline libapache2-mod-php5
```

## Configuration

* Add the following entry to `/etc/hosts`:

```
127.0.0.1       elecena.local
```

* Copy `../app/apache/000-elecena.conf` to `/etc/apache2/sites-available`.
* `sudo a2enmod rewrite && sudo a2enmod php5 && sudo a2ensite 000-elecena.conf`
* `sudo service apache2 reload`

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
