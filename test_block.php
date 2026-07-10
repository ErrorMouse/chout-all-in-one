<?php
define('ABSPATH', __DIR__ . '/');
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

function wp_is_cli() { return false; }
function wp_upload_dir() { return ['basedir' => __DIR__]; }
function sanitize_text_field($str) { return $str; }
function wp_unslash($str) { return $str; }
function wp_die($msg, $title, $args) { echo "BLOCKED: $msg\n"; exit; }
function esc_html__($str, $domain) { return $str; }

$file_path = __DIR__ . '/chout-aio-blocked-ips.php';
$export_data = ['127.0.0.1' => 1];
file_put_contents($file_path, "<?php\nreturn " . var_export($export_data, true) . ";\n");

require 'chout-all-in-one.php';

Chout_AIO_Block_IPs::check_and_block_ip();
echo "NOT BLOCKED\n";
