#yiimp - yaamp fork (added install.sh for all dependencies)
============================================================

# run install.sh 
*****Do not run the script as root*****

This script has an interactive beginning and will ask for the following information:

Your time zone
Server Name 
Support Email Address
Server Admin Email Address
If you would like fail2ban installed
If you would like to have SSL (LetsEncrypt) installed - Your domain must be pointed to your server prior to running the script or SSL will fail to install. 
New custom location for yiimp admin login. 

Once those questions are answered the script will then be fully automated for the rest of the install. 

1. Update and Upgrade Ubuntu Packages
2. Install Aptitude
3. Install and configure Nginx
4. Install MariaDB with random root password
5. Install php7
6. Install various dev packages required for building blocknotify and stratum
7. Install SendMail
8. Install Fail2Ban if selected
9. Install and configur phpmyadmin with random password for phpmyadmin user
10. Clone yiimp build packages, create directory structure, set file permissions, and more
11. Update server clock
12. Install LetsEncrypt if selected
13. Create yiimp database, create 2 users with random passwords - passwords saved in ~/.my.cnf
14. Import the sql dumps from yiimp
15. Create base yiimp serverconfig.php file to get you going
16. Updates all directory permissions

This install script will get you 95% ready to go with yiimp. There are a few things you need to do after the main install is finished.

You must update the following files:

1. /var/web/serverconfig.php - update this file to include your public ip to access the admin panel. update with public keys from exchanges. update with other information specific to your server..
2. /etc/yiimp/keys.php - update with secrect keys from the exchanges. 

After you add the missing information to those files then run:
bash main.sh
bash loop2.sh
bash block.sh

bash install.sh

-------------------------------------------------------------------------------------------------------------------------


Required:

	linux, mysql, php, memcached, a webserver (lighttpd or nginx recommended)


Config for nginx:

	location / {
		try_files $uri @rewrite;
	}

	location @rewrite {
		rewrite ^/(.*)$ /index.php?r=$1;
	}

	location ~ \.php$ {
		fastcgi_pass unix:/var/run/php5-fpm.sock;
		fastcgi_index index.php;
		include fastcgi_params;
	}


If you use apache, it should be something like that (already set in web/.htaccess):

	RewriteEngine on

	RewriteCond %{REQUEST_FILENAME} !-f
	RewriteRule ^(.*) index.php?r=$1 [QSA]


If you use lighttpd, use the following config:

	$HTTP["host"] =~ "yiimp.ccminer.org" {
	        server.document-root = "/var/yaamp/web"
	        url.rewrite-if-not-file = (
			"^(.*)/([0-9]+)$" => "index.php?r=$1&id=$2",
			"^(.*)\?(.*)" => "index.php?r=$1&$2",
	                "^(.*)" => "index.php?r=$1",
	                "." => "index.php"
	        )

		url.access-deny = ( "~", ".dat", ".log" )
	}


For the database, import the initial dump present in the sql/ folder

Then, apply the migration scripts to be in sync with the current git, they are sorted by date of change.

Your database need at least 2 users, one for the web site (php) and one for the stratum connections (password set in config/algo.conf).



The recommended install folder for the stratum engine is /var/stratum. Copy all the .conf files, run.sh, the stratum binary and the blocknotify binary to this folder. 

Some scripts are expecting the web folder to be /var/web. You can use directory symlinks...


Add your exchange API public and secret keys in these two separated files:

	/etc/yiimp/keys.php - fixed path in code
	web/serverconfig.php - use sample as base...

You can find sample config files in web/serverconfig.sample.php and web/keys.sample.php

This web application includes some command line tools, add bin/ folder to your path and type "yiimp" to list them, "yiimp checkup" can help to test your initial setup.
Future scripts and maybe the "cron" jobs will then use this yiic console interface.

You need at least three backend shells (in screen) running these scripts:

	web/main.sh
	web/loop2.sh
	web/block.sh

Start one stratum per algo using the run.sh script with the algo as parameter. For example, for x11:

	run.sh x11

Edit each .conf file with proper values.

Look at rc.local, it starts all three backend shells and all stratum processes. Copy it to the /etc folder so that all screen shells are started at boot up.

All your coin's config files need to blocknotify their corresponding stratum using something like:

	blocknotify=blocknotify yaamp.com:port coinid %s

On the website, go to http://server.com/site/adminRights to login as admin. You have to change it to something different in the code (web/yaamp/modules/site/SiteController.php). A real admin login may be added later, but you can setup a password authentification with your web server, sample for lighttpd:

	htpasswd -c /etc/yiimp/admin.htpasswd <adminuser>

and in the lighttpd config file:

	# Admin access
	$HTTP["url"] =~ "^/site/adminRights" {
	        auth.backend = "htpasswd"
	        auth.backend.htpasswd.userfile = "/etc/yiimp/admin.htpasswd"
	        auth.require = (
	                "/" => (
	                        "method" => "basic",
	                        "realm" => "Yiimp Administration",
	                        "require" => "valid-user"
	                )
	        )
	}

And finally remove the IP filter check in SiteController.php



There are logs generated in the /var/stratum folder and /var/log/stratum/debug.log for the php log.

More instructions coming as needed.


There a lot of unused code in the php branch. Lot come from other projects I worked on and I've been lazy to clean it up before to integrate it to yaamp. It's mostly based on the Yii framework which implements a lightweight MVC.

	http://www.yiiframework.com/


Credits:

Thanks to globalzon to have released the initial Yaamp source code.

--

You can support this project donating to tpruvot :

BTC : 1Auhps1mHZQpoX4mCcVL8odU81VakZQ6dR

