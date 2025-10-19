<?php

namespace Riyad\Polysms\Contracts;

use Riyad\Polysms\Contracts\GatewayContract;

/**
 * Interface GatewayRegistryContract
 *
 * Defines the contract for a registry that manages payment gateways.
 * Responsible for registering, retrieving, and managing gateway instances and metadata.
 */
interface GatewayRegistryContract
{
    /**
     * Register a gateway factory with optional metadata.
     *
     * @param string $name Unique gateway name
     * @param callable $factory Factory that returns an instance of GatewayContract
     * @param array $meta Optional metadata about the gateway
     * @return void
     *
     * @throws \InvalidArgumentException If the name is empty or the factory is not callable
     */
    public function register(string $name, callable $factory, array $meta = []): void;

    /**
     * Unregister a gateway by its name.
     *
     * @param string $name Name of the gateway to unregister
     * @return void
     *
     * @throws GatewayNotFoundException If the gateway is not registered
     */
    public function unregister(string $name): void;

    /**
     * Check if a gateway with the given name is registered.
     *
     * @param string $name Name of the gateway
     * @return bool True if registered, false otherwise
     */
    public function has(string $name): bool;

    /**
     * Get an instance of a registered gateway.
     *
     * @param string $name Name of the gateway
     * @return GatewayContract Instance of the requested gateway
     *
     * @throws GatewayNotFoundException If the gateway is not registered
     * @throws \RuntimeException If the factory does not return a valid GatewayContract instance
     */
    public function get(string $name): GatewayContract;

    /**
     * Get metadata associated with a specific gateway.
     *
     * @param string $name Name of the gateway
     * @return array Metadata array for the gateway
     *
     * @throws GatewayNotFoundException If the gateway is not registered
     */
    public function getMeta(string $name): array;

    /**
     * Get all registered gateways, either as names or as resolved instances.
     *
     * @param bool $asInstances If true, return instantiated GatewayContract objects; if false, return gateway names
     * @return array<string, GatewayContract>|string[] Array of gateway instances or names
     */
    public function all(bool $asInstances): array;

    /**
     * Clear all registered gateways and instances from the registry.
     *
     * Useful for resetting the registry or for testing purposes.
     *
     * @return void
     */
    public function clear(): void;
}