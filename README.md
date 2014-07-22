wp-migrate
==========

Painless Wordpress Migration

##Getting Started

Clone project into your Wordpress Site webroot
```bash
git clone https://github.com/micahblu/wp-migrate.git
```

CD into wp-migrate directory
```bash
cd wp-migrate
```

Ensure wp-migrate.php is executable
```bash
chmod +x wp-migrate.php
```

Run it passes the new site url via the `-s` flag
```bash
./wp-migrate.php -s http://newsiteurl.com
```


Flag Options
=
WP Migrate will attempt to autodetect from wp-config.php the values for the folowing flags. The only required flag is `-s`

 * -s new site url
 * -d database 
 * -h hostname 
 * -u username
 * -p password
