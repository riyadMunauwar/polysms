<?php 

namespace Riyad\Polysms\Contracts;

use Riyad\Polysms\Constants\HookReturnMode;
  
/**
 * Interface HookRegistryContract
 *
 * Defines the contract for a hook registry system.
 * Responsible for configuring hooks, registering/removing hooks,
 * executing hooks, and querying registered hooks.
 */
interface HookRegistryContract
{
    /**
     * Configure a hook with its behavior and validation rules.
     *
     * @param string $hookName The name/identifier of the hook
     * @param bool $allowMultiple Whether multiple hooks can be registered under this name
     * @param int $defaultPriority Default priority for hooks registered under this name
     * @param string $returnMode How the hook return value is handled. 
     *                           Expected values: HookReturnMode::IGNORE, HookReturnMode::SINGLE
     * @param array|null $strictContracts Optional array of fully-qualified interface names 
     *                                    that any hook registered under this name must implement
     * @return void
     */
    public function configureHook(
        string $hookName,
        bool $allowMultiple = true,
        int $defaultPriority = 0,
        string $returnMode = HookReturnMode::IGNORE,
        ?array $strictContracts = null
    ): void;

    /**
     * Register a hook for a given hook name.
     *
     * @param string $hookName The name of the hook
     * @param callable|object|string $hook A callable, object instance, or class name (string)
     * @param int|null $priority Optional priority (overrides default from configureHook)
     * @param array|null $contracts Optional array of fully-qualified interface names that the hook must implement
     * @return void
     *
     * @throws HookRegistrationException If the hook is invalid, does not exist, or fails contract validation
     */
    public function register(string $hookName, $hook, ?int $priority = null, ?array $contracts = null): void;

    /**
     * Remove a previously registered hook.
     *
     * If the hook is not found under the given hook name, nothing happens.
     *
     * @param string $hookName The name of the hook
     * @param callable|object|string $hook The hook to remove
     * @return void
     */
    public function remove(string $hookName, $hook): void;

    /**
     * Execute all hooks registered for the given hook name.
     *
     * - If multiple hooks are allowed (allowMultiple = true), return value is always null.
     * - If single hook and returnMode = SINGLE, returns the value from the executed hook.
     *
     * @param string $hookName The name of the hook to execute
     * @param mixed ...$args Arguments to pass to the hook callable or method
     * @return mixed|null The hook return value if SINGLE mode, or null otherwise
     *
     * @throws HookValidationException If a registered hook violates its contract/interface
     */
    public function execute(string $hookName, ...$args);

    /**
     * Check if there are any hooks registered under the given hook name.
     *
     * @param string $hookName The hook name to check
     * @return bool True if there are hooks registered, false otherwise
     */
    public function hasHooks(string $hookName): bool;

    /**
     * Get all registered hooks for a given hook name (read-only).
     *
     * Each entry in the returned array has the structure:
     * ['hook' => callable|object|string, 'priority' => int, 'contracts' => array|null]
     *
     * @param string $hookName The hook name
     * @return array List of registered hook entries
     */
    public function getHooks(string $hookName): array;
}