<?php

namespace Riyad\PolySms;

use Riyad\PolySms\Contracts\SmsManagerContract;
use Riyad\PolySms\Contracts\GatewayContract;
use Riyad\PolySms\Contracts\GatewayRegistryContract;
use Riyad\PolySms\DTO\BaseDTO;
use Riyad\PolySms\DTO\SmsResult;
use Riyad\PolySms\Constants\Hook;


class SmsManager implements SmsManagerContract
{
    /**
     * Singleton instance of SmsManager.
     *
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * Registry of available gateways.
     *
     * @var GatewayRegistryContract
     */
    private GatewayRegistryContract $registry;

    /**
     * Private constructor to prevent direct instantiation.
     *
     * @param GatewayRegistryContract $registry Gateway registry
     * @param HookRegistryContract $hookRegistry Hook registry
     */
    private function __construct(GatewayRegistryContract $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Initialize the singleton PaymentManager instance.
     * Only the first call sets the registry instances.
     *
     * @param GatewayRegistryContract $registry Gateway registry
     * @param HookRegistryContract $hookRegistry Hook registry
     * @return self
     */
    public static function init(GatewayRegistryContract $registry): self
    {
        if (!self::$instance) {
            self::$instance = new self($registry);
        }
        return self::$instance;
    }

    /**
     * Get the existing singleton instance of SmsManager.
     *
     * @return self
     * @throws \RuntimeException if the instance is not yet initialized
     */
    public static function instance(): self
    {
        if (!self::$instance) {
            throw new \RuntimeException("SmsManager not initialized. Call SmsManager::init() first.");
        }
        return self::$instance;
    }

    /**
     * Register a gateway factory with optional metadata.
     *
     * @param string $name Unique gateway name
     * @param callable $factory Factory that returns a GatewayContract instance
     * @param array $meta Optional metadata about the gateway
     * @return void
     * @throws \InvalidArgumentException If name is empty or factory is not callable
     */
    public function register(string $name, callable $factory, array $meta = []): void
    {
        $this->registry->register($name, $factory, $meta);
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
        $this->registry->unregister($name);
    }

    /**
     * Set the active gateway for payment processing.
     *
     * @param string $gateway Gateway name
     * @return static
     */
    public function gateway(string $gateway): GatewayContract
    {
        return $this->registry->get($gateway);
    }


    /**
     * Apply a callback to all registered gateways and return the results.
     *
     * @param callable $callback Function to execute for each gateway (GatewayContract $gateway): mixed
     * @return array<string, mixed> Array of results keyed by gateway name
     */
    public function map(callable $callback): array
    {
        $results = [];

        foreach ($this->registry->all(false) as $name) {
            $gateway = $this->registry->get($name); // ensures gateway is instantiated
            $results[] = $callback($gateway);
        }

        return $results;
    }

    /**
     * Filter gateways using a callback.
     *
     * @param callable $callback Function to filter gateways (GatewayContract $gateway): bool
     * @return array<string, GatewayContract> Filtered gateways keyed by name
     */
    public function filter(callable $callback): array
    {
        $filtered = [];

        foreach ($this->registry->all(false) as $name) {
            $gateway = $this->registry->get($name);
            if ($callback($gateway)) {
                $filtered[$name] = $gateway;
            }
        }

        return $filtered;
    }

}