<?php
namespace WooCart\CLI\DB;

if (!class_exists('WP_CLI')) {
    return;
}

/**
 * Rename all of WordPress' database tables
 *
 * @throws \Exception
 */
function rename_tables($new_prefix, $verbose)
{
    global $wpdb;
    $show_table_query = sprintf('SHOW TABLES LIKE "%s%%";', $wpdb->esc_like($wpdb->prefix));
    $tables = $wpdb->get_results($show_table_query, ARRAY_N);

    if (!$tables) {
        // Ask user if he wished to proceed with updating options and usermeta
        \WP_CLI::confirm( 'No tables found with ' . $old_prefix . ' prefix. Would you like to continue updating options and usermeta tables?' );
        return;
    }

    foreach ($tables as $table) {
        $table = substr($table[0], strlen($old_prefix));
        $query = sprintf("RENAME TABLE `%s` TO `%s`;", $wpdb->prefix. $table, $new_prefix . $table);
        if ($verbose) {
            \WP_CLI::line($query);
        }

        if (false === $wpdb->query($query)) {
            throw new \Exception('MySQL error: ' . $wpdb->last_error);
        }
    }
}

/**
 * Update rows in the `options` table
 *
 * @throws \Exception
 */

function update_options_table($old_prefix, $verbose)
{
    global $wpdb;
    $update_query = $wpdb->prepare("UPDATE `{$wpdb->prefix}options` SET option_name = %s WHERE option_name = %s LIMIT 1;",
        $wpdb->prefix . 'user_roles',
        $old_prefix . 'user_roles'
    );
    if ($verbose) {
        \WP_CLI::line($update_query);
        return;
    }
    if (!$wpdb->query($update_query)) {
        throw new \Exception('MySQL error: ' . $wpdb->last_error);
    }
}

/**
 * Update rows in the `usermeta` table
 *
 * @throws \Exception
 */
function update_usermeta_table($old_prefix, $verbose)
{
    global $wpdb;

    $rows = $wpdb->get_results("SELECT meta_key FROM `{$wpdb->prefix}usermeta`;");

    if (!$rows) {
        throw new \Exception('MySQL error: ' . $wpdb->last_error);
    }
    foreach ($rows as $row) {
        $meta_key_prefix = substr($row->meta_key, 0, strlen($old_prefix));
        if ($meta_key_prefix !== $old_prefix) {
            continue;
        }
        $new_key = $wpdb->prefix . substr($row->meta_key, strlen($old_prefix));
        $update_query = $wpdb->prepare("UPDATE `{$wpdb->prefix}usermeta` SET meta_key=%s WHERE meta_key=%s LIMIT 1;",
            $new_key,
            $row->meta_key
        );
        if ($verbose) {
            \WP_CLI::line($update_query);
        }
        if (!$wpdb->query($update_query)) {
            throw new \Exception('MySQL error: ' . $wpdb->last_error);
        }
    }
}

/**
 * Replace database engine.
 *
 * @throws \Exception
 */
function to_innodb($verbose)
{
    global $wpdb;

    $sql = "SELECT TABLE_NAME FROM Information_schema.TABLES WHERE TABLE_SCHEMA = '$wpdb->dbname' AND ENGINE = 'MyISAM' AND TABLE_TYPE='BASE TABLE'";
    $results = $wpdb->get_results($sql, ARRAY_N);
    foreach ($results as $result) {

        $table_name = $result[0];

        $sql = "ALTER TABLE $table_name ENGINE=InnoDB;";
        if ($verbose) {
            \WP_CLI::line($sql);
        }
        if (!$wpdb->query($sql)) {
            throw new \Exception('MySQL error: ' . $wpdb->last_error);
        }
    }
}

/**
 * Replace database tables prefix and update options table.
 *
 * ## OPTIONS
 *
 * <old_prefix>
 * : Old prefix.
 *
 * [--verbose]
 * : Show more information about the process on STDOUT.
 */
$run_prefix_replace_command = function ($args, $assoc_args) {
    $old_prefix = array_shift($args);
    $defaults = array(
        'verbose' => true,
    );
    $assoc_args = array_merge($defaults, $assoc_args);
    $verbose = \WP_CLI\Utils\get_flag_value($assoc_args, 'verbose');

    rename_tables($old_prefix, $verbose);

};

\WP_CLI::add_command('woocart prefix-replace', $run_prefix_replace_command);

/**
 * Replace database tables prefix and update options table.
 *
 * ## OPTIONS
 *
 * [--verbose]
 * : Show more information about the process on STDOUT.
 */
$run_innodb_command = function ($args, $assoc_args) {
    $defaults = array(
        'verbose' => true,
    );
    $assoc_args = array_merge($defaults, $assoc_args);
    $verbose = \WP_CLI\Utils\get_flag_value($assoc_args, 'verbose');

    to_innodb($verbose);

};

\WP_CLI::add_command('woocart to-innodb', $run_innodb_command);

/**
  * Add a plugin to denylist.
  *
  * ## EXAMPLES
  *
  *     woocart denylist my_plugin
  *
  * @param $args array list of command line arguments.
  * @param $assoc_args array of named command line keys.
  */
$run_denylist_command = function( $args, $assoc_args ) {
    list( $plugin ) = $args;

    // Fetch current denylist from the options table
    $denylist = get_option( 'woocart_denylist_plugins', [] );

    // Add plugin to the list
    if ( ! empty( $plugin ) ) {
        if ( ! in_array( $plugin, $denylist ) ) {
            $denylist[] = $plugin;

            update_option( 'woocart_denylist_plugins', $denylist );
            \WP_CLI::success( sprintf( '%s plugin added to the denylist.', $plugin ) );
        } else {
            \WP_CLI::log( 'Plugin already exists in the denylist.' );
        }
    } else {
        \WP_CLI::error( 'No plugin specified.' );
    }
};

\WP_CLI::add_command( 'woocart denylist', $run_denylist_command );

/**
  * Add a plugin to allowlist.
  *
  * ## EXAMPLES
  *
  *     woocart allowlist my_plugin
  *
  * @param $args array list of command line arguments.
  * @param $assoc_args array of named command line keys.
  */
$run_allowlist_command = function( $args, $assoc_args ) {
    list( $plugin ) = $args;

    // Fetch current allowlist from the options table
    $allowlist = get_option( 'woocart_allowlist_plugins', [] );

    // Add plugin to the list
    if ( ! empty( $plugin ) ) {
        if ( ! in_array( $plugin, $allowlist ) ) {
            $allowlist[] = $plugin;

            update_option( 'woocart_allowlist_plugins', $allowlist );
            \WP_CLI::success( sprintf( '%s plugin added to the allowlist.', $plugin ) );
        } else {
            \WP_CLI::log( 'Plugin already exists in the allowlist.' );
        }
    } else {
        \WP_CLI::error( 'No plugin specified.' );
    }
};

\WP_CLI::add_command( 'woocart allowlist', $run_allowlist_command );
