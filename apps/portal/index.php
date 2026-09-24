<?php
/**
 * InfoLayer Portal Bootstrap & Router
 */

define('INFOLAYER_PORTAL', true);
define('BASE_PATH', __DIR__);

$config_file = BASE_PATH . '/config/app.config.php';
$setup_complete_file = BASE_PATH . '/storage/secure/.setup_complete';

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

// Check if setup is needed
if (!file_exists($config_file) || !file_exists($setup_complete_file)) {
    if (strpos($uri, '/setup') !== 0) {
        header('Location: /setup/');
        exit;
    }
} else {
    // Prevent access to setup after completion
    if (strpos($uri, '/setup') === 0) {
        http_response_code(403);
        echo "403 Forbidden - Setup is locked.";
        exit;
    }
}

// Router
if (strpos($uri, '/setup') === 0) {
    require_path_or_404(BASE_PATH . '/setup/index.php');
} elseif (strpos($uri, '/api') === 0) {
    require_path_or_404(BASE_PATH . '/api/index.php');
} elseif (strpos($uri, '/admin') === 0) {
    require_path_or_404(BASE_PATH . '/admin/index.php');
} elseif (strpos($uri, '/church') === 0) {
    require_path_or_404(BASE_PATH . '/church/index.php');
} else {
    // Default home or redirect to login
    if (file_exists($config_file) && file_exists($setup_complete_file)) {
        header('Location: /church/login.php');
    } else {
        header('Location: /setup/');
    }
    exit;
}

function require_path_or_404($file) {
    if (file_exists($file)) {
        require_once $file;
    } else {
        http_response_code(404);
        echo "404 Not Found";
    }
}
