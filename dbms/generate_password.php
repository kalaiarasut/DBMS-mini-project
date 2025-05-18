<?php
$password = 'manager123';
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Password hash for '$password': " . $hash;
?> 