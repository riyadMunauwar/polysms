<?php

declare(strict_types=1);

namespace Riyad\PolySms;

use Riyad\Hooks\Hook;

/**
 * Class SmsHook
 *
 * A package-specific singleton wrapper around Hook.
 * Prevents cross-package singleton conflicts by
 * maintaining its own isolated Hook instance.
 *
 * Example:
 *  $SmsHook = SmsHook::instance();
 *  $SmsHook->addAction('init', fn() => echo "App init");
 *  $SmsHook->doAction('init');
 */
class SmsHook
{
    /**
     * Singleton instance of SmsHook.
     */
    private static ?SmsHook $instance = null;

    /**
     * Internal Hook instance (isolated from global Hook::instance()).
     */
    private Hook $hook;

    /**
     * Private constructor to enforce singleton.
     */
    private function __construct()
    {
        // Create a dedicated Hook object (not using Hook::instance)
        $this->hook = Hook::make();
    }

    /**
     * Get singleton instance of SmsHook.
     */
    public static function instance(): SmsHook
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Proxy calls to the internal Hook instance.
     * This lets SmsHook access all Hook methods transparently.
     */
    public function __call(string $name, array $arguments)
    {
        if (method_exists($this->hook, $name)) {
            return $this->hook->{$name}(...$arguments);
        }

        throw new \BadMethodCallException("Method {$name} does not exist on Hook.");
    }

    /**
     * Optionally expose the internal Hook instance if needed.
     */
    public function getHook(): Hook
    {
        return $this->hook;
    }
}
