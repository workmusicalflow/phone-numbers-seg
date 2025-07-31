<?php

// Buffer output to prevent "headers already sent" warnings
ob_start();

// Clear PHP session
session_start();
session_destroy();
echo "PHP session cleared.\n";

// Clear session cookies
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
    echo "Session cookie cleared.\n";
}

// Flush the output buffer
ob_end_flush();

// Clear other cookies
foreach ($_COOKIE as $name => $value) {
    setcookie($name, '', time() - 3600, '/');
    echo "Cookie '$name' cleared.\n";
}

echo "\nAll server-side caches and cookies have been cleared.\n";
echo "Please also clear your browser cache and cookies manually:\n";
echo "1. In Chrome: Ctrl+Shift+Delete (Windows/Linux) or Cmd+Shift+Delete (Mac)\n";
echo "2. In Firefox: Ctrl+Shift+Delete (Windows/Linux) or Cmd+Shift+Delete (Mac)\n";
echo "3. In Safari: Cmd+Option+E\n";
echo "\nAfter clearing browser cache, restart your browser and try logging in again.\n";
