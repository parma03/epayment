<?php
// config/midtrans_config.php
// Fixed Midtrans Configuration File

class MidtransConfig
{
    // Sandbox Environment - Updated with your actual keys
    const SERVER_KEY_SANDBOX = 'Mid-server-Cb96pXISJY2A3GnsGPcM-349';
    const CLIENT_KEY_SANDBOX = 'Mid-client-i_gMpoalNpsZFVjf';

    // Production Environment  
    const SERVER_KEY_PRODUCTION = 'YOUR_PRODUCTION_SERVER_KEY_HERE';
    const CLIENT_KEY_PRODUCTION = 'YOUR_PRODUCTION_CLIENT_KEY_HERE';

    // Environment Setting
    const IS_PRODUCTION = false;

    // Additional Midtrans Settings
    const IS_SANITIZED = true;
    const IS_3DS = true;

    public static function getServerKey()
    {
        $key = self::IS_PRODUCTION ? self::SERVER_KEY_PRODUCTION : self::SERVER_KEY_SANDBOX;

        // Debug: Log the key (remove in production)
        error_log("Midtrans Server Key: " . $key);

        return $key;
    }

    public static function getClientKey()
    {
        $key = self::IS_PRODUCTION ? self::CLIENT_KEY_PRODUCTION : self::CLIENT_KEY_SANDBOX;

        // Debug: Log the key (remove in production)
        error_log("Midtrans Client Key: " . $key);

        return $key;
    }

    public static function getSnapUrl()
    {
        return self::IS_PRODUCTION
            ? 'https://app.midtrans.com/snap/snap.js'
            : 'https://app.sandbox.midtrans.com/snap/snap.js';
    }

    /**
     * Auto-configure Midtrans if class exists
     */
    public static function autoConfig()
    {
        if (class_exists('\Midtrans\Config')) {
            $serverKey = self::getServerKey();

            // Validate key before setting
            if (empty($serverKey) || $serverKey === 'YOUR_PRODUCTION_SERVER_KEY_HERE') {
                throw new Exception('Midtrans server key is not properly configured');
            }

            \Midtrans\Config::$serverKey = $serverKey;
            \Midtrans\Config::$isProduction = self::IS_PRODUCTION;
            \Midtrans\Config::$isSanitized = self::IS_SANITIZED;
            \Midtrans\Config::$is3ds = self::IS_3DS;

            // Debug: Verify configuration
            error_log("Midtrans Config Set - ServerKey: " . \Midtrans\Config::$serverKey);
            error_log("Midtrans Config Set - IsProduction: " . (\Midtrans\Config::$isProduction ? 'true' : 'false'));
        } else {
            throw new Exception('Midtrans\Config class not found. Please check if Midtrans library is properly installed.');
        }
    }

    /**
     * Get all configuration as array
     */
    public static function getAllConfig()
    {
        return [
            'server_key' => self::getServerKey(),
            'client_key' => self::getClientKey(),
            'is_production' => self::IS_PRODUCTION,
            'is_sanitized' => self::IS_SANITIZED,
            'is_3ds' => self::IS_3DS,
            'snap_url' => self::getSnapUrl()
        ];
    }

    /**
     * Validate if keys are properly configured
     */
    public static function validateConfig()
    {
        $serverKey = self::getServerKey();
        $clientKey = self::getClientKey();

        if (empty($serverKey) || $serverKey === 'YOUR_PRODUCTION_SERVER_KEY_HERE') {
            throw new Exception('Midtrans server key is not properly configured');
        }

        if (empty($clientKey) || $clientKey === 'YOUR_PRODUCTION_CLIENT_KEY_HERE') {
            throw new Exception('Midtrans client key is not properly configured');
        }

        // Additional validation for key format
        if (!preg_match('/^Mid-server-/', $serverKey) && !self::IS_PRODUCTION) {
            throw new Exception('Invalid Midtrans server key format for sandbox environment');
        }

        if (!preg_match('/^Mid-client-/', $clientKey) && !self::IS_PRODUCTION) {
            throw new Exception('Invalid Midtrans client key format for sandbox environment');
        }

        return true;
    }

    /**
     * Initialize Midtrans configuration
     * Call this method before using any Midtrans functionality
     */
    public static function init()
    {
        try {
            self::validateConfig();
            self::autoConfig();
            return true;
        } catch (Exception $e) {
            error_log("Midtrans Config Error: " . $e->getMessage());
            throw $e;
        }
    }
}

// Auto-initialize when file is included
try {
    MidtransConfig::init();
} catch (Exception $e) {
    // Log error but don't throw to prevent breaking the application
    error_log("Failed to initialize Midtrans config: " . $e->getMessage());
}
?>