<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://https://github.com/blitheforge
 * @since      1.0.0
 *
 * @package    Sohojpaybd
 * @subpackage Sohojpaybd/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Sohojpaybd
 * @subpackage Sohojpaybd/admin
 * @author     Blithe Forge <blitheforge@gmail.com>
 */
class Sohojpaybd_Admin
{

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles()
	{
		$allowed = array('sohojpaybd-transactions', 'sohojpaybd');
		if (isset($_GET['page']) && in_array($_GET['page'], $allowed)) {
			wp_enqueue_style($this->plugin_name . '-style', plugin_dir_url(__FILE__) . 'css/sohojpaybd.css', array(), $this->version, 'all');
			wp_enqueue_style($this->plugin_name . '-datatable', plugin_dir_url(__FILE__) . 'css/datatables.min.css', array(), $this->version, 'all');
		}

		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/sohojpaybd-admin.css', array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{
		$allowed = array('sohojpaybd-transactions', 'sohojpaybd');
		if (isset($_GET['page']) && in_array($_GET['page'], $allowed)) {
			wp_enqueue_script($this->plugin_name . '-datatable', plugin_dir_url(__FILE__) . 'js/datatables.min.js', array('jquery'), $this->version, false);
			wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/sohojpaybd-admin.js', array('jquery'), $this->version, false);
		}
	}

	/**
	 * Register the admin menu and submenu pages.
	 *
	 * @since    1.0.0
	 */
	public function sohojpaybd_admin_menu()
	{
		add_menu_page('Sohojpay BD', 'Sohojpay BD', 'manage_options', 'sohojpaybd', array($this, 'sohojpaybd_menu'), plugin_dir_url(__FILE__) . 'images/logo.png');
		add_submenu_page('sohojpaybd', 'Sohojpay Settings', 'Sohojpay Settings', 'manage_options', 'sohojpaybd', array($this, 'sohojpaybd_menu'));
		add_submenu_page('sohojpaybd', 'Your Orders', 'Your Orders', 'manage_options', 'edit.php?post_type=shop_order'); // Link to WC orders page
		add_submenu_page('sohojpaybd', 'Sohojpay Transactions', 'Sohojpay Transactions', 'manage_options', 'sohojpaybd-transactions', array($this, 'sohojpay_transactions'));
	}

	/**
	 * Display the settings page content.
	 *
	 * @since    1.0.0
	 */
	public function sohojpaybd_menu()
	{
?>
		<div class="wrap">
			<form method="POST" action="options.php">
				<?php
				settings_fields('sohojpaybd_settings');
				do_settings_sections('sohojpaybd');
				submit_button('Save Settings');
				?>
			</form>
		</div>
	<?php
	}

	/**
	 * Fetch data from the database.
	 *
	 * @since    1.0.0
	 * @param    string    $table_name    The table name.
	 * @param    string    $condition     The condition for the query.
	 * @param    string    $return_type   The return type.
	 * @return   array                    The query results.
	 */
	private function fetch_data($table_name, $condition = null, $return_type = OBJECT)
	{
		global $wpdb;

		$full_table_name = $wpdb->prefix . $table_name;

		$query = "SELECT * FROM $full_table_name";

		if ($condition) {
			$query .= " WHERE $condition";
		}

		$results = $wpdb->get_results($query, $return_type);

		return $results;
	}

	/**
	 * Display the transactions page content.
	 *
	 * @since    1.0.0
	 */
	public function sohojpay_transactions()
	{
		$items = $this->fetch_data('sohojpay_transactions');

		ob_start();
		require_once plugin_dir_path(__FILE__) . 'partials/sohojpaybd-admin-display.php';
		$template = ob_get_contents();
		ob_end_clean();
		echo $template;
	}

	/**
	 * Display the API key input field.
	 *
	 * @since    1.0.0
	 */
	public function sohojpaybd_api_html()
	{
		$sohojpaybd_api_key = get_option('sohojpaybd_api_key'); ?>
		<input type="text" size="70" name='sohojpaybd_api_key' value="<?php echo esc_attr($sohojpaybd_api_key); ?>" required>
<?php
	}

	/**
	 * Register the plugin settings.
	 *
	 * @since    1.0.0
	 */
	public function sohojpaybd_settings_init()
	{
		add_settings_section(
			'sohojpaybd_setting_first_section',
			'SohojpayBD WP Setup',
			null,
			'sohojpaybd'
		);

		// API key
		register_setting('sohojpaybd_settings', 'sohojpaybd_api_key', array('sanitize_callback' => 'sanitize_text_field'));
		add_settings_field(
			'sohojpaybd_api_key',
			'SohojpayBD Api Key',
			array($this, 'sohojpaybd_api_html'),
			'sohojpaybd',
			'sohojpaybd_setting_first_section'
		);
	}
}


?>