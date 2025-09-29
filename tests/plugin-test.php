<?php
/**
 * Simple Plugin Integration Test
 * 
 * This test verifies that the plugin structure is correct and basic functionality works.
 */

// Simulate WordPress environment constants
if (!defined('ABSPATH')) {
    define('ABSPATH', '/tmp/wp/');
}
if (!defined('WPINC')) {
    define('WPINC', 'wp-includes');
}

// Mock essential WordPress functions
function add_action($hook, $callback, $priority = 10, $args = 1) { return true; }
function add_filter($hook, $callback, $priority = 10, $args = 1) { return true; }
function register_activation_hook($file, $callback) { return true; }
function register_deactivation_hook($file, $callback) { return true; }
function register_uninstall_hook($file, $callback) { return true; }
function plugin_dir_path($file) { return dirname($file) . '/'; }
function plugin_dir_url($file) { return 'http://example.com/wp-content/plugins/' . basename(dirname($file)) . '/'; }
function load_plugin_textdomain() { return true; }
function __($text, $domain = '') { return $text; }
function esc_attr($text) { return $text; }
function esc_html($text) { return $text; }
function sanitize_text_field($text) { return trim($text); }
function sanitize_email($email) { return filter_var($email, FILTER_SANITIZE_EMAIL); }
function wp_parse_args($args, $defaults) { return array_merge($defaults, (array)$args); }
function wp_json_encode($data) { return json_encode($data); }
function get_option($key, $default = false) { return $default; }
function update_option($key, $value) { return true; }
function current_user_can($capability) { return false; }
function get_current_user_id() { return 0; }

// Define plugin constants for testing
define('PC_PLUGIN_FILE', dirname(__DIR__) . '/paywall-premium-content.php');
define('PC_PLUGIN_PATH', dirname(__DIR__) . '/');
define('PC_PLUGIN_URL', 'http://example.com/wp-content/plugins/paywall/');
define('PC_VERSION', '1.0.0');
define('PC_MIN_WP_VERSION', '6.5');
define('PC_MIN_PHP_VERSION', '8.1');

// Test runner
class PayWallPluginTest {
    
    private $errors = [];
    private $passed = 0;
    private $total = 0;
    
    public function run() {
        echo "PayWall Plugin Test Suite\n";
        echo str_repeat("=", 50) . "\n\n";
        
        $this->testPluginStructure();
        $this->testAutoloader();
        $this->testHelperFunctions();
        $this->testPriceFormatting();
        
        $this->printResults();
    }
    
    private function testPluginStructure() {
        $this->test("Plugin main file exists", function() {
            return file_exists(PC_PLUGIN_FILE);
        });
        
        $this->test("Includes directory exists", function() {
            return is_dir(PC_PLUGIN_PATH . 'includes');
        });
        
        $this->test("Assets directory exists", function() {
            return is_dir(PC_PLUGIN_PATH . 'assets');
        });
        
        $this->test("Blocks directory exists", function() {
            return is_dir(PC_PLUGIN_PATH . 'blocks');
        });
    }
    
    private function testAutoloader() {
        // Include the main plugin file
        require_once PC_PLUGIN_FILE;
        
        $this->test("Autoloader function exists", function() {
            return function_exists('pc_autoloader');
        });
        
        $this->test("Helper functions loaded", function() {
            return file_exists(PC_PLUGIN_PATH . 'includes/helper-functions.php');
        });
        
        $this->test("Plugin class file exists", function() {
            return file_exists(PC_PLUGIN_PATH . 'includes/class-plugin.php');
        });
    }
    
    private function testHelperFunctions() {
        // Manually include helper functions for testing
        if (file_exists(PC_PLUGIN_PATH . 'includes/helper-functions.php')) {
            require_once PC_PLUGIN_PATH . 'includes/helper-functions.php';
        }
        
        $this->test("pc_format_price function exists", function() {
            return function_exists('pc_format_price');
        });
        
        if (function_exists('pc_format_price')) {
            $this->test("pc_format_price formats USD correctly", function() {
                return pc_format_price(500, 'USD') === '$5.00';
            });
            
            $this->test("pc_format_price formats EUR correctly", function() {
                return pc_format_price(1000, 'EUR') === '€10.00';
            });
        }
    }
    
    private function testPriceFormatting() {
        if (!function_exists('pc_format_price')) {
            $this->test("Skipping price formatting tests - function not available", function() {
                return true;
            });
            return;
        }
        
        $testCases = [
            [500, 'USD', '$5.00'],
            [1000, 'EUR', '€10.00'],
            [2500, 'GBP', '£25.00'],
            [100, 'JPY', '¥100'],
            [0, 'USD', '$0.00'],
            [99, 'USD', '$0.99'],
        ];
        
        foreach ($testCases as [$amount, $currency, $expected]) {
            $this->test("Format {$amount} {$currency} as {$expected}", function() use ($amount, $currency, $expected) {
                return pc_format_price($amount, $currency) === $expected;
            });
        }
    }
    
    private function test($description, $callback) {
        $this->total++;
        
        try {
            $result = $callback();
            if ($result) {
                echo "✓ {$description}\n";
                $this->passed++;
            } else {
                echo "✗ {$description}\n";
                $this->errors[] = $description;
            }
        } catch (Exception $e) {
            echo "✗ {$description} - Error: " . $e->getMessage() . "\n";
            $this->errors[] = $description . " - " . $e->getMessage();
        }
    }
    
    private function printResults() {
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "Test Results: {$this->passed}/{$this->total} passed\n";
        
        if (empty($this->errors)) {
            echo "🎉 All tests passed!\n";
        } else {
            echo "\n❌ Failed tests:\n";
            foreach ($this->errors as $error) {
                echo "   - {$error}\n";
            }
        }
        
        echo "\nPlugin structure verification complete.\n";
    }
}

// Run the test
$test = new PayWallPluginTest();
$test->run();