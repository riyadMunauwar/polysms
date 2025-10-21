<?php 

namespace Riyad\PolySms\Constants;

/**
 * Class Hook
 *
 * Defines string constants for named hooks used in the sms system.
 *
 * - `BEFORE_SMS_SENT`: Triggered before a sms is sent.
 *
 * This class is `final` and cannot be instantiated.
 */
final class Hook 
{
    /**
     * Private constructor to prevent instantiation.
     */
    private function __construct() {}

    /** 
     * Hook name executed before a sms end.
     */
    public const BEFORE_SMS_SENT = 'polysms.beforeSmsSent';
    public const AFTER_SMS_SENT = 'polysms.afterSmsSent';
}