# WP DB Backup Using Cron

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public 
License for more details.

## What is it?
This tool is a script that can be run using a cron. It will automatically dump all of the 
tables for each site into a separate .sql file. The files will be stored in separate folders 
in a sub folder where this script is located. It will also automatically create the needed 
folders. These files can can be kept for a specified amount of time and then automatically 
deleted when then time expires. This script was originally buiilt to work with multisite 
but has been modified to also deal with single site WP installations.

This script has been modified so that it will also work on single site wp installations.

## Why do I use this?
I need a simple, fullproof way to back up a database regularly. DB dumps for multisite 
installations are generally too large to import again, and quite frankly, the available WP 
tools for backups create files that require too much time and effort to work with or 
require constant fiddling with settings to make sure you're backing up the right tables 
for each site.

DB backups are important, not just for disaster recovery but also to deal with user error. 
Having files that can be used easly and quickly makes the job of recovery clean and simple.

## What's included here?
This script uses the following libraries
* [MySQLDump - PHP](https://github.com/ifsnop/mysqldump-php) from [Diego Torres](https://github.com/ifsnop)
* bluntMysqli: This is a MySQLi class that I built several years ago when I was doing a lot more DB work,
but still comes in handy because it already has built into it the things for the way I work.

## How it Works
The cron script will automatically read the connection information from the wp-config.php file for your site. 
(Please see the security section in the setup instructions.) It then gets a list of all of the tables in the
database, sorts them and groups them by site ID. Once this is done it creates a DB dumb for all of the tables
for each site. After the DB dump process it then reads the list of exsitng files and deletes anything older
than the age specified in the script's setting.

Only active sites listed in the _blogs table will be dumped so if there happens to be stray tables that were not
deleted properly when a site was deleted then they will be ignored.

## Setup

##### 1) Create a Folder
Create a folder off the root of your site. This folder needs to be at the same level as wp-config.php. 
I like to use __cron as my folder name, but the name does not really matter as long as it's in 
the right place. So for example if your WP site is installed in 
`/usr/local/www.yoursite.com/public` then you would create a folder at
`/usr/local/www.yoursite.com/public/__cron`, or whatever name you choose.

##### 2) Security
Prevent access to this folder from the web. This is extremely important because this script 
automatically reads the connection settings from wp-config.php. While I do not believe that 
it's possible for someone to gain access to your database through this script, it never hurts 
to be extra cautious. Generally I do this by adding a 403 rewrite rule in my .htaccess file like this:
```
RewriteEngine On
RewriteRule ^__cron - [R=403,L]
RewriteEngine Off
```

##### 3) Download & Extract
Download this repo and extract the files into your new folder. These files should not be in a sub-folder.

##### 4) Settings
Open the file db-backup-part-1.php and edit the settings at the top of the file to specify your time 
zone and the amount of time that you want to keep the backup files. The servers where I host 
my sites require that I set the default time zone before I use any date functions. Your server 
may have other requirements and they should be set up here as well.
```
define('TIMEZONE', 'America/Chicago'); // used when calling date_default_timezone_set()
define('ARCHIVE_REMOVAL', '-30 days'); // backups before this time are deleted
```
the value for `ARCHIVE_REMOVAL` will be automatically calculated base on the current date - (minus) 
whatever value you set here. For example, if you wanted to keep the backup files for a year then 
you could set it to `'-1 Year'`

**Extremely Large Multisite Networks**

The time limit set for the running of this script is 30 minutes. This happens to be the maximum time
limit on all crons run on my current hosting environment. If you are running an extremely large multisite
environment and you're hosting provider or your server sets a maximum time for crons then you can
split up your crons into multiple instances. Create a copy of db-backup-part-1 for each section 
that you want to backup and set these contants:
```
define('SITE_START', 0); // first site to backup
define('SITE_END', 0);  // last site to backup
```
A value of 0 for SITE_START indicates all sites <= SITE_END
A value of 0 for SITE_END indicates all sites >= SITE_START

##### 5) Write Permissions
Make sure that PHP has permission to write to the folder on the server. If PHP cannot write to the 
folder then the script will fail. This may require differnet things for differnet hosting environments. 
I'll assume that you know how to do this for your hosting environment and will not go into all the possbilities.

##### 6) Set up the cron
Set up a cron that points to the PHP script (db-backup-part-1.php, or if running multiple crons for extremely large multisite environments that then the file name of your script). This cannot a web URL:
1. we have made the url not accessible form the web (I hope, see security)
2. the script would likely time out anyway if you do try to run it from a browser or accessing it via URL

Again, the details of setting up a cron on your particular hosting environment are left up to you. 
I don't know every host and how this can be accomplished for your environment.

Set the cron to run as often as you want to have a backup created. As a general rule I set it to 
run every night at about 2 or 3 AM and I keep the files for 30 days. This is usually sufficient 
but you may have other needs.

##### 7) Make sure it's running
I cannot guarantee that it will work, but you should test it before you assume it's just going to work. 
When I'm setting this up I usually set it to iniiially run every 5 minutes or so and set the cron to 
send me emails so I can see that it has run and get any error reports and then look at the server 
and make sure the files were saved and that they are in fact valid .sql files

## Where are the db dump files?
The script will automatically create a subfolder inside the folder where it is located named 
`sitess` if it does not exist. Then it will create a subfolder for each site using the site's ID 
if these folders do not exsit.

The file names of the .sql files you'll find in this folder will be of the form
`YYYY-MM-DD_HH-MM-SS.sql`. The file names are importent as this is how the script knows 
when each file needs to be deleted.

# Just To Make Sure - Security of this folder is important and IT IS YOUR RESPONBILITY, Not mine.
I don't know what can be found in your DB dumps, but since anyone could read these files if you 
do not secure them it is extremely important that you make sure they are not accessible from the web. 
I have included a .htaccess file that might help, but I will not promise that it will keep your 
files secure. You can never have too much security.

### Donations
If you find my work useful and you have a desire to send me money, which will give me an incentive to continue
offering and maintaining the plugins I've made public in my many repositories, I'm not going to turn it down
and whatever you feel my work is worth will be greatly appreciated. You can send money through paypal to
hube02[AT]earthlink[dot]net. 
