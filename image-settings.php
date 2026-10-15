<?php
$imageSettings = [
    'login' => 'assets/images/taylorlogin.jpg',
    'register' => 'assets/images/oliviaregister.jpg',
    'forgot_password' => 'assets/images/floatingclothesforgot.jpg',
    'dashboard_hero' => 'assets/images/sgadashboard.jpg',
    'dashboard_cards' => [
        ['url' => 'assets/images/clairocard.jpg', 'title' => 'Everyday style'],
        ['url' => 'assets/images/sgacard.jpg', 'title' => 'New-season layers'],
        ['url' => 'assets/images/clairocard.jpg', 'title' => 'Pieces to love'],
    ],
];

$isStylesheetRequest = isset($_SERVER['SCRIPT_FILENAME'])
    && realpath($_SERVER['SCRIPT_FILENAME'])
    && realpath(__FILE__)
    && strcasecmp(realpath($_SERVER['SCRIPT_FILENAME']), realpath(__FILE__)) === 0;

if ($isStylesheetRequest) {
    header('Content-Type: text/css; charset=UTF-8');
    $cssUrl = static function (string $url): string {
        $escaped = str_replace(["\\", '"', "\r", "\n"], ["\\\\", '\\"', '', ''], $url);
        return 'url("' . $escaped . '")';
    };
    echo ':root{'
        . '--image-login:' . $cssUrl($imageSettings['login']) . ';'
        . '--image-register:' . $cssUrl($imageSettings['register']) . ';'
        . '--image-forgot-password:' . $cssUrl($imageSettings['forgot_password']) . ';'
        . '--image-dashboard-hero:' . $cssUrl($imageSettings['dashboard_hero']) . ';'
        . '}';
    exit;
}

return $imageSettings;
