# WordPress database tools

## Using

~~~
wp db prefix-replace new_prefix [--verbose]
~~~

Replaces table names with new_prefix ones and updates **options** and **usermeta** tables.

~~~
wp db to-innodb [--verbose]
~~~

Replaces table engine to **InnoDB**.

## Installing

Installing this package requires WP-CLI v0.23.0 or greater. Update to the latest stable release with `wp cli update`.

Once you've done so, you can install this package with `wp package install woocart/wp-cli-dbtools`.