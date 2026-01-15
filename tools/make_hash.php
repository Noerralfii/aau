<?php
if($argc < 2){ echo "Usage: php make_hash.php <password>\n"; exit(1); }
$password = $argv[1];
echo password_hash($password, PASSWORD_DEFAULT) . "\n";
?>