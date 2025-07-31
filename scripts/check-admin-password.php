<?php
require_once __DIR__ . '/../vendor/autoload.php';

$hash = '$2y$10$usKkfE9JC5z.DCYrrm1TD.A5iqZRxiGLgz7J8.XkVFi7k5Dc0yZyK';

$passwords = [
    'admin',
    'admin123',
    'MotDePasseSecure2024!!',
    'password',
    'admin@2024'
];

echo "Test des mots de passe potentiels :\n";
foreach ($passwords as $password) {
    if (password_verify($password, $hash)) {
        echo "✓ Mot de passe trouvé : $password\n";
    } else {
        echo "✗ $password\n";
    }
}