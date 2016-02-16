# eID (Client Side Certificate) Login for WordPress

Contributors: kasparsd   
Tags: authentication, ssl, certificate, login, eid, yubikey, crypto   
Requires at least: 4.0   
Tested up to: 4.4   
Stable tag: trunk  

A helper plugin to login using Client Side Certificates, including European identity cards (eID, EstEID, beID, etc.).


## Description

### Requirements

- A subdomain (i.e. `https://eid.example.com`) that is configured to perform the client certificate validation and pass the results as `SSL_CLIENT_VERIFY` and `SSL_CLIENT_FINGERPRINT` environment variables to the WordPress login page `https://example.com/wp-login.php` which in turn issues a login cookie.

- Defined `COOKIE_DOMAIN` as `.example.com` (or similar) because the whole process relies on WordPress issuing an authentication cookie from a subdomain for the top level domain.


#### Nginx Configuration

Here is an example of an Nginx configuration for the authentication server. The WordPress site is hosted at `example.com` while `eid.example.com` is doing the client certificate validation and passing those results back to WordPress on the same server via `PHP-FPM`, which issues a login cookie if the validation has been successful and a user with that certificate fingerprint has been found.

	#
	# Client certificate authentication server at eid.example.com
	#
	server {
		listen 443 ssl;
		server_name eid.example.com;

		# Location of your WordPress instance
		root /var/www/example.com/public;

		# Server SSL certificates
		ssl_certificate  /etc/nginx/certs/eid/server.crt;
		ssl_certificate_key  /etc/nginx/certs/eid/server.key;

		# Certificate Authority that signed the client certificates
		ssl_client_certificate /etc/nginx/certs/eid/eid-ca.crt;

		ssl_verify_depth 3;

		# Non-verfied users will get SSL_CLIENT_VERIFY set to NONE
		ssl_verify_client optional;

		location /wp-login.php {
				try_files $uri =404;
				fastcgi_pass unix:/var/run/php-fpm.socket;
				include fastcgi.conf;

				# Fake the hostname for WordPress to avoid redirects
				fastcgi_param  HTTP_HOST  example.com;

				# Pass back the results of the client certificate authentication
				fastcgi_param  SSL_CLIENT_CERT         $ssl_client_raw_cert if_not_empty;
				fastcgi_param  SSL_CLIENT_FINGERPRINT  $ssl_client_fingerprint if_not_empty;
				fastcgi_param  SSL_CLIENT_VERIFY       $ssl_client_verify if_not_empty;
		}

		location / {
				# Redirect all non-login requests back to the main domain
				return 301 $scheme://example.com;
		}

	}

### SHA1 fingerprint

You can find the SHA1 fingerprint of your client certificate during the certificate selection process in the browser ([screenshot](https://github.com/kasparsd/eid-login/raw/master/screenshot-3.png)).

### Screenshots

- [eID Login URL setting](https://github.com/kasparsd/eid-login/raw/master/screenshot-1.png) and
- [User certificate fingerprint setting](https://github.com/kasparsd/eid-login/raw/master/screenshot-2.png)


## Installation

Extract the zip file and just drop the contents in the `wp-content/plugins/` directory of your WordPress installation and then activate the Plugin from Plugins page.


## Screenshots

1. eID Login URL setting
2. User certificate fingerprint


## Changelog

### 0.1 (February 15, 2016)

- First release.
