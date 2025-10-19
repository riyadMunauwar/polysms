<?php

namespace Riyad\Polysms;

use Riyad\Polysms\Contracts\SmsManagerContract;
use Riyad\Polysms\Contracts\GatewayContract;
use Riyad\Polysms\Contracts\GatewayRegistryContract;
use Riyad\Polysms\DTO\SmsDTO;
use Riyad\Polysms\DTO\SmsResult;
use Riyad\Polysms\Constants\Hook;
use Riyad\Polysms\Constants\HookReturnMode;
use Riyad\Polysms\Contracts\BeforeSmsSentContract;
use Riyad\Polysms\Contracts\HookRegistryContract;
use Riyad\Polysms\Exceptions\UnsupportedFeatureException;

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
     * Registry of hooks for sms processing events.
     *
     * @var HookRegistryContract
     */
    private HookRegistryContract $hookRegistry;

    /**
     * Currently selected gateway for processing sms.
     *
     * @var GatewayContract|null
     */
    private ?GatewayContract $currentGateway = null;

    /**
     * Private constructor to prevent direct instantiation.
     *
     * @param GatewayRegistryContract $registry Gateway registry
     * @param HookRegistryContract $hookRegistry Hook registry
     */
    private function __construct(GatewayRegistryContract $registry, HookRegistryContract $hookRegistry)
    {
        $this->registry = $registry;
        $this->hookRegistry = $hookRegistry;
    }

    /**
     * Initialize the singleton PaymentManager instance.
     * Only the first call sets the registry instances.
     *
     * @param GatewayRegistryContract $registry Gateway registry
     * @param HookRegistryContract $hookRegistry Hook registry
     * @return self
     */
    public static function init(GatewayRegistryContract $registry, HookRegistryContract $hookRegistry): self
    {
        if (!self::$instance) {
            self::$instance = new self($registry, $hookRegistry);
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
            throw new \RuntimeException("PaymentManager not initialized. Call PaymentManager::init() first.");
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
    public function gateway(string $gateway): static
    {
        $this->currentGateway = $this->registry->get($gateway);
        return $this;
    }

    /**
     * Get the gateway instance from the registry.
     *
     * @param string $gateway The identifier of the gateway to retrieve.
     *
     * @return GatewayContract The resolved gateway instance.
     */
    public function getGateway(string $gateway) : GatewayContract
    {
        return $this->registry->get($gateway);
    }

    /**
     * Process a payment through the currently selected gateway.
     *
     * @param SmsDTO $dto Sms data transfer object
     * @return SmsResult
     * @throws \RuntimeException if no gateway is selected or DTO is invalid
     */
    public function send(SmsDTO $dto): SmsResult
    {
        $this->ensureGatewayIsSelected();

        if (!$dto instanceof SmsDTO) {
            throw new \RuntimeException("Provided DTO must return an instance of SmsDTO");
        }

        // Execute beforeSmsSent hook
        $dto = $this->hookRegistry->execute(
            Hook::BEFORE_SMS_SENT,
            $dto,
            $this->currentGateway->name()
        );

        // Process sms via gateway
        $result = $this->currentGateway->send($dto);

        return $result;
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

    /**
     * Register a hook to execute before payment processing.
     *
     * @param string|callable|BeforeSmsSentContract $hook Hook callback, class name, or instance
     * @return void
     */
    public function onBeforeSmsSent(string|callable|BeforeSmsSentContract $hook): void
    {
        if(is_callable($hook)){
            $this->hookRegistry->configureHook(
                Hook::BEFORE_SMS_SENT,
                allowMultiple: false,
                defaultPriority: 0,
                returnMode: HookReturnMode::SINGLE,
            );

        }else {
            $this->hookRegistry->configureHook(
                Hook::BEFORE_SMS_SENT,
                allowMultiple: false,
                defaultPriority: 0,
                returnMode: HookReturnMode::SINGLE,
                strictContracts: [BeforeSmsSentContract::class]
            );
        }

        $this->hookRegistry->register(
            Hook::BEFORE_SMS_SENT,
            $hook,
        );
    }

    
    /**
     * Ensure a sms gateway has been selected.
     *
     * @return void
     * @throws \RuntimeException if no gateway is selected
     */
    private function ensureGatewayIsSelected(): void
    {
        if (!$this->currentGateway) {
            throw new \RuntimeException("No gateway selected. Please call gateway() before proceeding.");
        }
    }
}