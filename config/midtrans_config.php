<?php
// Midtrans Configuration File
// Buat file ini di folder config/

class MidtransConfig
{
    // Sandbox Environment
    const SERVER_KEY_SANDBOX = 'Mid-server-Cb96pXISJY2A3GnsGPcM-349';
    const CLIENT_KEY_SANDBOX = 'Mid-client-i_gMpoalNpsZFVjf';

    // Production Environment  
    const SERVER_KEY_PRODUCTION = 'YOUR_PRODUCTION_SERVER_KEY_HERE';
    const CLIENT_KEY_PRODUCTION = 'YOUR_PRODUCTION_CLIENT_KEY_HERE';

    // Environment Setting
    const IS_PRODUCTION = false; // Set to true for production

    public static function getServerKey()
    {
        return self::IS_PRODUCTION ? self::SERVER_KEY_PRODUCTION : self::SERVER_KEY_SANDBOX;
    }

    public static function getClientKey()
    {
        return self::IS_PRODUCTION ? self::CLIENT_KEY_PRODUCTION : self::CLIENT_KEY_SANDBOX;
    }

    public static function getSnapUrl()
    {
        return self::IS_PRODUCTION
            ? 'https://app.midtrans.com/snap/snap.js'
            : 'https://app.sandbox.midtrans.com/snap/snap.js';
    }
}

// Auto-configure Midtrans jika sudah di-include
if (class_exists('\Midtrans\Config')) {
    \Midtrans\Config::$serverKey = MidtransConfig::getServerKey();
    \Midtrans\Config::$isProduction = MidtransConfig::IS_PRODUCTION;
    \Midtrans\Config::$isSanitized = true;
    \Midtrans\Config::$is3ds = true;
}
?>