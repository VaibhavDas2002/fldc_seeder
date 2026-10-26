<?php

// Start session
session_start();

// Function to print nicely
function print_section($title, $data)
{
    echo "\n==================== $title ====================\n";
    if (empty($data)) {
        echo "No data\n";
    } else {
        print_r($data);
    }
}

// Set plain text for readability
header('Content-Type: text/plain');

// 1. Request Headers
if (function_exists('getallheaders')) {
    $headers = getallheaders();
} else {
    // Fallback for non-Apache
    $headers = [];
    foreach ($_SERVER as $name => $value) {
        if (substr($name, 0, 5) == 'HTTP_') {
            $headers[str_replace('_', '-', substr($name, 5))] = $value;
        }
    }
}

print_section('REQUEST HEADERS', $headers);

// 2. Session Data
print_section('SESSION DATA', $_SESSION);

// 3. Cookies
print_section('COOKIES', $_COOKIE);

// 4. Server Variables
print_section('SERVER VARIABLES', $_SERVER);

// 5. Request Parameters (GET + POST)
print_section('GET PARAMETERS', $_GET);
print_section('POST PARAMETERS', $_POST);

// 6. Raw Input (for API / JSON debugging)
$rawInput = file_get_contents('php://input');
print_section('RAW INPUT', $rawInput);
