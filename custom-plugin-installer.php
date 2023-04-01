<?php
/*
Plugin Name: Custom Plugin Installer
Description: A custom plugin to quickly install predefined plugins from the WordPress plugin marketplace.
Version: 1.0
Author: Your Name
*/

// Create custom menu page
function custom_plugin_installer_menu() {
	add_menu_page(
		'Custom Plugin Installer', // Page title
		'Plugin Installer', // Menu title
		'manage_options', // Capability
		'custom-plugin-installer', // Menu slug
		'custom_plugin_installer_options', // Function to display the content
		'dashicons-admin-plugins', // Icon URL or Dashicon
		100 // Position
	);
}
add_action('admin_menu', 'custom_plugin_installer_menu');

// Display custom options page content
function custom_plugin_installer_options() {
	if (!current_user_can('manage_options')) {
		wp_die(__('You do not have sufficient permissions to access this page.'));
	}
	wp_enqueue_script('custom-plugin-installer-script', plugin_dir_url(__FILE__) . 'custom-plugin-installer.js', ['jquery'], '1.0', true);
	wp_localize_script('custom-plugin-installer-script', 'ajax_object', [
		'ajax_url' => admin_url('admin-ajax.php'),
	]);
	wp_enqueue_style('custom-plugin-installer-style', plugin_dir_url(__FILE__) . 'custom-plugin-installer.css', [], '1.0');

	?>
    <div class="wrap">
        <h1>Custom Plugin Installer</h1>
        <p>Select a plugin to install:</p>
        <div id="plugin-card-container">
            <!-- Plugin cards will be added here -->
        </div>
    </div>
	<?php
}

function custom_plugin_installer_is_active() {
	if (!current_user_can('install_plugins')) {
		wp_send_json_error(['message' => 'You do not have sufficient permissions to check plugin activation status.']);
	}

	if (!isset($_POST['slug'])) {
		wp_send_json_error(['message' => 'Invalid plugin slug.']);
	}

	$slug = $_POST['slug'];
	$plugins = get_plugins('/' . $slug);

	if (empty($plugins)) {
		wp_send_json_error(['active' => false]);
	}

	$plugin_path = $slug . '/' . key($plugins);

	if (is_plugin_active($plugin_path)) {
		wp_send_json_success(['active' => true]);
	} else {
		wp_send_json_success(['active' => false]);
	}
}
add_action('wp_ajax_custom_plugin_installer_is_active', 'custom_plugin_installer_is_active');

// Handle AJAX request for checking if a plugin is installed
function custom_plugin_installer_check_installed() {
	if (!current_user_can('install_plugins')) {
		wp_send_json_error(['installed' => false]);
	}

	if (!isset($_POST['slug'])) {
		wp_send_json_error(['installed' => false]);
	}

	$slug = $_POST['slug'];
	$installed_plugin = get_plugins('/' . $slug);
	wp_send_json_success(['installed' => !empty($installed_plugin)]);
}
add_action('wp_ajax_custom_plugin_installer_check_installed', 'custom_plugin_installer_check_installed');

// Handle AJAX request for installing a plugin
function custom_plugin_installer_install() {
	if (!current_user_can('install_plugins')) {
		wp_send_json_error(['message' => 'You do not have sufficient permissions to install plugins.']);
	}

	if (!isset($_POST['slug'])) {
		wp_send_json_error(['message' => 'Invalid plugin slug.']);
	}

	include_once(ABSPATH . 'wp-admin/includes/plugin-install.php');
	include_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');
	include_once(ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php');

	$slug = $_POST['slug'];
	$source = isset($_POST['source']) ? $_POST['source'] : 'repository';
	$plugin_file = isset($_POST['file']) ? $_POST['file'] : '';

// Check if the plugin is already installed
	$installed_plugin = get_plugins('/' . $slug);
	if (!empty($installed_plugin)) {
		wp_send_json_error(['message' => 'The plugin is already installed.']);
	}

	$upgrader = new Plugin_Upgrader(new Automatic_Upgrader_Skin());

	if ($source === 'repository') {
		// Get plugin information
		$plugin_info = plugins_api('plugin_information', ['slug' => $slug]);
		if (is_wp_error($plugin_info)) {
			wp_send_json_error(['message' => 'Failed to retrieve plugin information.']);
		}

		// Install the plugin
		$result = $upgrader->install($plugin_info->download_link);
	} else if ($source === 'directory') {
		// Install the plugin from a zip file in the plugins directory
		$plugin_zip = WP_PLUGIN_DIR . '/custom-plugin-installer/plugins/' . $plugin_file;
		$result = $upgrader->install($plugin_zip);
	} else {
		wp_send_json_error(['data' => ['message' => 'Invalid plugin source.']]);
	}

	if (is_wp_error($result) || !$result) {
		wp_send_json_error(['message' => 'Failed to install the plugin.']);
	}

	wp_send_json_success(['message' => 'Plugin installed successfully!']);
}
add_action('wp_ajax_custom_plugin_installer_install', 'custom_plugin_installer_install');