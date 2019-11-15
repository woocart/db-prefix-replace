# WordPress database tools

## Using

~~~
wp woocart prefix-replace new_prefix [--verbose]
~~~

Replaces table names with new_prefix ones and updates **options** and **usermeta** tables.

~~~
wp woocart to-innodb [--verbose]
~~~

Replaces table engine to **InnoDB**.

~~~
wp woocart denylist my-plugin
~~~

Adds plugin to the denylist

~~~
wp woocart allowlist my-plugin
~~~

Adds plugin to the allowlist (whitelisted)

## Installing

Installing this package requires WP-CLI v0.23.0 or greater. Update to the latest stable release with `wp cli update`.

Once you've done so, you can install this package with `wp package install woocart/wp-cli-dbtools`.
