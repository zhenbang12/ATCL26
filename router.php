<?php
/**
 * Router for PHP built-in server
 * Handles static files and routes requests to index.php
 */

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// List of directories that should be served as static files
$staticDirs = ['/uploads/', '/css/', '/js/', '/images/'];

foreach ($staticDirs as $dir) {
    if (strpos($requestUri, $dir) === 0) {
        $filePath = __DIR__ . $requestUri;
        
        // Security: prevent directory traversal
        $realPath = realpath($filePath);
        $docRoot = realpath(__DIR__);
        
        if ($realPath && strpos($realPath, $docRoot) === 0 && is_file($realPath)) {
            $ext = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
            if (in_array($ext, ['php', 'phtml', 'phar', 'env', 'sql', 'sh', 'bat', 'lock', 'git'], true)) {
                http_response_code(403);
                die('Access forbidden.');
            }

            // Determine the correct content type
            $contentTypes = [
                'jpg'   => 'image/jpeg',
                'jpeg'  => 'image/jpeg',
                'png'   => 'image/png',
                'gif'   => 'image/gif',
                'webp'  => 'image/webp',
                'svg'   => 'image/svg+xml',
                'css'   => 'text/css',
                'js'    => 'application/javascript',
                'json'  => 'application/json',
                'txt'   => 'text/plain',
            ];
            
            $contentType = $contentTypes[$ext] ?? 'application/octet-stream';
            
            header("Content-Type: $contentType");
            readfile($realPath);
            return true;
        }
    }
}

// Route to index.php for all other requests
require __DIR__ . '/index.php';
