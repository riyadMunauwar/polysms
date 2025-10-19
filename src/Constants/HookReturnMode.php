<?php

namespace Riyad\Polysms\Constants;

/**
 * Class HookReturnMode
 *
 * Defines the return mode for hooks in the sms system.
 *
 * - `IGNORE`: Ignore the return value of hooks (default for multiple hooks).
 * - `SINGLE`: Return the value from a single hook (when only one hook is allowed).
 *
 * This class is `final` and cannot be instantiated.
 */
final class HookReturnMode 
{
    /**
     * Private constructor to prevent instantiation.
     */
    private function __construct() {}

    /**
     * Ignore the return value of the hook.
     */
    public const IGNORE = 'ignore';

    /**
     * Return the value from a single hook.
     */
    public const SINGLE = 'single';
}