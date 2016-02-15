# eID (Client Side Certificate) Login for WordPress

Contributors: kasparsd   
Tags: authentication, ssl, certificate, login, eid   
Requires at least: 4.0   
Tested up to: 4.4   
Stable tag: trunk  

A helper plugin to login using Client Side Certificates, including European identity cards (eID, EstEID, beID, etc.).


## Description

### Requirements

A subdomain (i.e. `https://eid.example.com`) that is configured to perform the client certificate validation and pass the results as `SSL_CLIENT_VERIFY` and `SSL_CLIENT_FINGERPRINT` environment variables to the WordPress login page `https://example.com/wp-login.php`.

### Screenshots

- [eID Login URL setting](https://github.com/kasparsd/eid-login/raw/master/screenshot-1.png) and - [User certificate fingerprint setting](https://github.com/kasparsd/eid-login/raw/master/screenshot-2.png)


## Installation

Extract the zip file and just drop the contents in the wp-content/plugins/ directory of your WordPress installation and then activate the Plugin from Plugins page.


## Screenshots

1. eID Login URL setting
2. User certificate fingerprint


## Changelog

### 0.1 (February 15, 2016)

- First release.
