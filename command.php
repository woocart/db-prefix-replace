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
        $table = substr($table[0], strlen($new_prefix));
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
 * Replace database tables prefix and update options table.
 */
\WP_CLI::add_command('db rename-tables', function ($args, $assoc_args) {
    rename_tables("wp_", $verbose);
});


/**
 * Update rows in the `options` table
 *
 * @throws \Exception
 */

function update_options_table($old_prefix, $verbose)
{
    global $wpdb;
    $update_query = $wpdb->prepare("UPDATE `{$wpdb->prefix}options` SET option_name = %s WHERE option_name = %s LIMIT 1;",
        'wp_user_roles',
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
 * Update rows in the `options` table
 *
 * ## OPTIONS
 *
 * <old_prefix>
 * : Old prefix.
 *
 * [--verbose]
 * : Show more information about the process on STDOUT.
 */
\WP_CLI::add_command('db rename-options', function ($args, $assoc_args) {
    $old_prefix = array_shift($args);
    $defaults = array(
        'verbose' => true,
    );
    $assoc_args = array_merge($defaults, $assoc_args);
    $verbose = \WP_CLI\Utils\get_flag_value($assoc_args, 'verbose');
    update_options_table($old_prefix, $verbose);
});

/**
 * Update rows in the `usermeta` table
 *
 * @throws \Exception
 */
function update_usermeta_table($old_prefix, $verbose)
{
    global $wpdb;

    $rows = $wpdb->get_results("select meta_key from `{$wpdb->prefix}usermeta` where meta_key like '{$old_prefix}_%' group by meta_key;");

    if (!$rows) {
        throw new \Exception('MySQL error: ' . $wpdb->last_error);
    }
    foreach ($rows as $row) {
        $without_key_prefix = substr($row->meta_key, strlen($old_prefix));
        $update_query = $wpdb->prepare("UPDATE `{$wpdb->prefix}usermeta` SET meta_key=%s WHERE meta_key=%s LIMIT 1;",
            $wpdb->prefix . $without_key_prefix,
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
\WP_CLI::add_command('db rename-usermeta', function ($args, $assoc_args) {
    $old_prefix = array_shift($args);
    $defaults = array(
        'verbose' => true,
    );
    $assoc_args = array_merge($defaults, $assoc_args);
    $verbose = \WP_CLI\Utils\get_flag_value($assoc_args, 'verbose');
    update_usermeta_table($old_prefix, $verbose);
});
