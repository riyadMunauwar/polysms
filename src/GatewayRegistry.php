<?php

namespace Riyad\Polysms;

use Riyad\Polysms\Contracts\GatewayRegistryContract;
use Riyad\Polysms\Contracts\GatewayContract;
use Riyad\Polysms\Exceptions\GatewayNotFoundException;

/**
 * Class GatewayRegistry
 *
 * Manages registration, retrieval, and instantiation of gateways.
 * Implements singleton pattern to ensure a single registry instance.
 *
 * @implements GatewayRegistryContract
 */
class GatewayRegistry implements GatewayRegistryContract
{
    /**
     * Singleton instance of GatewayRegistry.
     *
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * Registered gateway factories and optional metadata.
     *
     * Format: ['gatewayName' => ['factory' => callable, 'meta' => array]]
     *
     * @var array<string, array{factory: callable, meta: array}>
     */
    private array $factories = [];

    /**
     * Instantiated gateways.
     *
     * @var array<string, GatewayContract>
     */
    private array $instances = [];

    /**
     * Private constructor to prevent direct instantiation.
     */
    private function __construct() {}

    /**
     * Initialize the singleton registry.
     *
     * @return self Singleton instance
     */
    public static function init(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get the existing singleton instance of the registry.
     *
     * @return self
     * @throws \RuntimeException If the registry is not initialized
     */
    public static function instance(): self
    {
        if (!self::$instance) {
            throw new \RuntimeException("GatewayRegistry not initialized. Call GatewayRegistry::init() first.");
        }
        return self::$instance;
    }

    /**
     * Register a gateway factory with optional metadata.
     *
     * @param string $name Unique gateway name
     * @param callable $factory Factory that returns an instance of GatewayContract
     * @param array $meta Optional metadata for the gateway
     * @return void
     * @throws \InvalidArgumentException If name is empty or factory is not callable
     */
    public function register(string $name, callable $factory, array $meta = []): void
    {
        if (empty($name)) {
            throw new \InvalidArgumentException("Gateway name must be provided and non-empty.");
        }

        if (!is_callable($factory)) {
            throw new \InvalidArgumentException("Factory for '{$name}' must be callable.");
        }

        $this->factories[$name] = ['factory' => $factory, 'meta' => $meta];
    }

    /**
     * Unregister a gateway.
     *
     * @param string $name Gateway name
     * @return void
     * @throws GatewayNotFoundException If the gateway is not registered
     */
    public function unregister(string $name): void
    {
        if (!$this->has($name)) {
            throw new GatewayNotFoundException("Cannot unregister '{$name}'; gateway not found.");
        }

        unset($this->factories[$name], $this->instances[$name]);
    }

    /**
     * Check if a gateway is registered.
     *
     * @param string $name Gateway name
     * @return bool True if registered, false otherwise
     */
    public function has(string $name): bool
    {
        return isset($this->factories[$name]);
    }

    /**
     * Get an instance of a registered gateway.
     *
     * @param string $name Gateway name
     * @return GatewayContract Instantiated gateway
     * @throws GatewayNotFoundException If the gateway is not registered
     * @throws \RuntimeException If the factory does not return a GatewayContract
     */
    public function get(string $name): GatewayContract
    {
        if (isset($this->instances[$name])) {
            return $this->instances[$name];
        }

        if (!$this->has($name)) {
            throw new GatewayNotFoundException("Gateway '{$name}' not registered.");
        }

        $entry = $this->factories[$name];
        $instance = call_user_func($entry['factory'], $this);

        if (!$instance instanceof GatewayContract) {
            throw new \RuntimeException("Factory for '{$name}' did not return a GatewayContract instance.");
        }

        $this->instances[$name] = $instance;

        return $instance;
    }

    /**
     * Get metadata for a specific gateway.
     *
     * @param string $name Gateway name
     * @return array Metadata associated with the gateway
     * @throws GatewayNotFoundException If the gateway is not registered
     */
    public function getMeta(string $name): array
    {
        if (!$this->has($name)) {
            throw new GatewayNotFoundException("Gateway '{$name}' not registered.");
        }

        return $this->factories[$name]['meta'];
    }

    /**
     * Get all registered gateway names or instantiated gateway objects.
     *
     * @param bool $asInstances If true, return instantiated gateways; otherwise return names
     * @return array<string, GatewayContract>|array<int, string>
     */
    public function all(bool $asInstances = true): array
    {
        if (!$asInstances) {
            return array_keys($this->factories);
        }

        $instances = [];

        foreach ($this->factories as $name => $_ignore) {
            $instances[$name] = $this->get($name);
        }

        return $instances;
    }

    /**
     * Clear all registered gateway factories and instances.
     *
     * Useful for testing or resetting the registry.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->factories = [];
        $this->instances = [];
    }
}