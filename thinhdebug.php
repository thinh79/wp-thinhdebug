<?php

/**
 * Plugin Name: Thinh Debug
 * Description: A debugging plugin to log time, HTTP information, and hooks.
 * Version: 1.0
 * Author: Your Name
 */

// Ensure WordPress has been loaded before running the plugin
if (!function_exists('add_action')) {
	echo 'This is a WordPress plugin and cannot be called directly.';
	exit;
}

// Register settings
function thinh_debug_register_settings()
{
	register_setting('thinh_debug_settings', 'thinh_debug_captured_url');
	register_setting('thinh_debug_settings', 'thinh_debug_hook_name');
	register_setting('thinh_debug_settings', 'thinh_debug_hook_list', 'thinh_debug_sanitize_hook_list');
}

add_action('admin_init', 'thinh_debug_register_settings');

// Sanitize hook list
function thinh_debug_sanitize_hook_list($input)
{
	// Sanitization logic for the hook list
	return $input;
}

// Add admin menu
function thinh_debug_admin_menu()
{
	add_options_page('Thinh Debug Settings', 'Thinh Debug', 'manage_options', 'thinh-debug', 'thinh_debug_settings_page');
}

add_action('admin_menu', 'thinh_debug_admin_menu');

// Settings page HTML
function thinh_debug_settings_page()
{
?>
	<div class="wrap">
		<h1>Thinh Debug Settings</h1>
		<form method="post" action="options.php">
			<?php settings_fields('thinh_debug_settings'); ?>
			<?php do_settings_sections('thinh_debug_settings'); ?>

			<table class="form-table">
				<tr valign="top">
					<th scope="row">Captured URL</th>
					<td><input type="text" name="thinh_debug_captured_url" value="<?php echo esc_attr(get_option('thinh_debug_captured_url')); ?>" /></td>
				</tr>
				<tr valign="top">
					<th scope="row">Hook Name</th>
					<td><input type="text" name="thinh_debug_hook_name" value="<?php echo esc_attr(get_option('thinh_debug_hook_name')); ?>" /></td>
				</tr>
			</table>

			<?php submit_button(); ?>
		</form>
	</div>
<?php
}

// Step 3: Enqueue JavaScript for Live Search
function thinh_debug_enqueue_scripts($hook)
{
	if ($hook !== 'settings_page_thinh-debug') {
		return;
	}

	wp_enqueue_script('thinh-debug-ajax', plugins_url('/js/thinh-debug-ajax.js', __FILE__), array('jquery'));
	wp_localize_script('thinh-debug-ajax', 'thinhDebugAjax', array('ajax_url' => admin_url('admin-ajax.php')));
}

add_action('admin_enqueue_scripts', 'thinh_debug_enqueue_scripts');

// Step 4: AJAX Handler for Live Search
function thinh_debug_ajax_search_hooks()
{
	// Assume $available_hooks is an array of hook names
	$available_hooks = ['init', 'wp_loaded', 'wp_head', 'the_content', 'wp_footer']; // Example hooks

	$search_term = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';

	$matching_hooks = array_filter($available_hooks, function ($hook) use ($search_term) {
		return strpos($hook, $search_term) !== false;
	});

	wp_send_json_success(array_values($matching_hooks));
}

add_action('wp_ajax_thinh_debug_search_hooks', 'thinh_debug_ajax_search_hooks');

// Step 5: JavaScript File for Live Search (thinh-debug-ajax.js)
// Create a JavaScript file in the js folder of your plugin directory.
// Created in: /wp-content/plugins/thinhdebug/js/thinh-debug-ajax.js


// Main function for logging
function thinh_debug_log_page_visit()
{
	$log_file = ABSPATH . 'wp-content/thinhdebug.log';
	$current_time = current_time('mysql');
	$requested_url = $_SERVER['REQUEST_URI'];

	// If a captured URL is set, check if it matches the requested URL using regex
	$captured_url = get_option('thinh_debug_captured_url', ''); // Get the captured URL setting, default to empty
	if ($captured_url && !preg_match("#{$captured_url}#", $requested_url)) {
		// If it doesn't match, don't log and just return
		return;
	}

	// Start logging
	$log_data = "Time: {$current_time}\n";
	$log_data .= "URL: {$requested_url}\n";

	// Check for redirects
	if (isset($_SERVER['HTTP_REFERER'])) {
		$log_data .= "Redirected from: {$_SERVER['HTTP_REFERER']}\n";
	}

	// Append hooks

	// #1 Hook
	// Inside thinh_debug_log_page_visit function

	global $wp_filter;
	$hooks = $wp_filter['the_content']->callbacks; // Example for 'the_content' hook

	if (!empty($hooks)) {
		$log_data .= "Hooks Used:\n";
		foreach ($hooks as $priority => $hooked_functions) {
			foreach ($hooked_functions as $function) {
				$function_name = $function['function'];
				if (is_string($function_name)) {
					$log_data .= "Hook: {$function_name} at priority {$priority}\n";
				}
			}
		}
	}

	// Write to log file
	file_put_contents($log_file, $log_data, FILE_APPEND);
}

add_action('wp_loaded', 'thinh_debug_log_page_visit');

function thinh_debug_activate()
{
	// Activation code here
}
register_activation_hook(__FILE__, 'thinh_debug_activate');

function thinh_debug_deactivate()
{
	// Deactivation code here
}
register_deactivation_hook(__FILE__, 'thinh_debug_deactivate');
