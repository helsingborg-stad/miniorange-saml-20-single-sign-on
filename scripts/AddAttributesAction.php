<?php

$filePath = __DIR__ . '/../class-mo-saml-login-validate.php';
$searchLine = '$this->mo_saml_login_user( $user_email, $first_name, $last_name, $user_name, $group_name, $default_role, $relay_state, $check_if_match_by );';
$prependComment = '
        // Add attributes action, to allow other plugins to handle data.
        do_action("MiniOrange/SamlCheckMapping/AttibutesListener", $attrs); 
        
';

if (!file_exists($filePath)) {
    echo "File not found: $filePath\n";
    exit(1);
}

$fileContents = file($filePath, FILE_IGNORE_NEW_LINES);
$updatedContents = [];

foreach ($fileContents as $line) {
    if (strpos($line, $searchLine) !== false) {
        $updatedContents[] = $prependComment . $line; // Prepend the comment
    } else {
        $updatedContents[] = $line;
    }
}

file_put_contents($filePath, implode(PHP_EOL, $updatedContents));

echo "File modified successfully: $filePath\n";