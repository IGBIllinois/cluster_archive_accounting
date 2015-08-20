How to install archive_accounting

Dependencies
1. PHP
2. PHP MySQL
3. PHP LDAP
4. PHP XML

Installation
1. Create an alias in apache pointing to html folder
2. Give apache read access to the archive folder
3. Run sql/archive_accounting.sql to create the database
4. Create a MySQL user with select/insert/delete/update permissions on the archive_accounting database
5. Edit /conf/settings.inc.php to reflect your settings
6. To /etc/crontab add

0 5 L * * root php bin/accounting.php

7. Create at least one admin user in the database