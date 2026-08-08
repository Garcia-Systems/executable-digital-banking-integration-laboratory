<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use Harbor\DigitalBankingLab\Http\HttpKernelFactory;

header_remove('X-Powered-By');
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin === 'http://127.0.0.1:5173') {
    // Chapter 12's deliberately narrow development policy; production policy is deployment-specific.
    header('Access-Control-Allow-Origin: http://127.0.0.1:5173');
    header('Vary: Origin');
    header('Access-Control-Allow-Headers: Content-Type, Accept');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
}
$length = isset($_SERVER['CONTENT_LENGTH']) ? (int) $_SERVER['CONTENT_LENGTH'] : 0;
$body = $length > 65_536 ? str_repeat('x', 65_537) : (file_get_contents('php://input', false, null, 0, 65_537) ?: '');
HttpKernelFactory::create()->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/', $body, ['Content-Type' => $_SERVER['CONTENT_TYPE'] ?? ''])->send();
