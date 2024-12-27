<?php
/*
Plugin Name: Woo Windcode
Description: Menambahkan kode unik pembayaran pada setiap pesanan di WooCommerce.
Version: 1.0
Author: Edwin - Astama Technology
Author URI: https://astamatechnology.com
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class WooWindcode {
    private $option_name = 'woo_windcode_settings';

    public function __construct() {
        // Add admin menu.
        add_action('admin_menu', [$this, 'add_admin_menu']);

        // Add settings link.
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'settings_link']);

        // Hook to WooCommerce checkout process.
        add_action('woocommerce_checkout_update_order_meta', [$this, 'add_unique_code']);

        // Hook to modify the total amount.
        add_filter('woocommerce_order_amount_total', [$this, 'modify_order_total'], 10, 2);
    }

    public function add_admin_menu() {
        add_menu_page(
            'Woo Windcode Uniq',
            'Woo Windcode Uniq',
            'manage_options',
            'woo_windcode',
            [$this, 'settings_page'],
            'dashicons-admin-tools'
        );
    }

    public function settings_link($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=woo_windcode') . '">Settings</a>';
        array_push($links, $settings_link);
        return $links;
    }

    public function settings_page() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['woo_windcode_save'])) {
            $min = isset($_POST['woo_windcode_min']) ? intval($_POST['woo_windcode_min']) : 501;
            $max = isset($_POST['woo_windcode_max']) ? intval($_POST['woo_windcode_max']) : 999;
            if ($min >= 0 && $max > $min) {
                update_option($this->option_name, ['min' => $min, 'max' => $max]);
            }
        }

        $settings = get_option($this->option_name, ['min' => 501, 'max' => 999]);
        ?>
        <div class="wrap">
            <h1>Woo Windcode Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="woo_windcode_min">Nominal Awal</label>
                        </th>
                        <td>
                            <input name="woo_windcode_min" id="woo_windcode_min" type="number" value="<?php echo esc_attr($settings['min']); ?>" min="0" required />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="woo_windcode_max">Nominal Akhir</label>
                        </th>
                        <td>
                            <input name="woo_windcode_max" id="woo_windcode_max" type="number" value="<?php echo esc_attr($settings['max']); ?>" min="0" required />
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" name="woo_windcode_save" class="button button-primary">Save Changes</button>
                </p>
            </form>
        </div>
        <?php
    }

    public function add_unique_code($order_id) {
        $settings = get_option($this->option_name, ['min' => 501, 'max' => 999]);
        $min = $settings['min'];
        $max = $settings['max'];

        $existing_codes = get_transient('woo_windcode_used') ?: [];
        $unique_code = mt_rand($min, $max);

        while (in_array($unique_code, $existing_codes)) {
            $unique_code = mt_rand($min, $max);
        }

        $existing_codes[] = $unique_code;
        set_transient('woo_windcode_used', $existing_codes, 2 * DAY_IN_SECONDS);

        update_post_meta($order_id, '_unique_code', $unique_code);
    }

    public function modify_order_total($total, $order) {
        $unique_code = get_post_meta($order->get_id(), '_unique_code', true);
        if ($unique_code) {
            $total += $unique_code;
        }
        return $total;
    }
}

new WooWindcode();
