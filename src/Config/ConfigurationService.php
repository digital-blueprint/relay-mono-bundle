<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Config;

use Symfony\Component\HttpFoundation\UrlHelper;
use Symfony\Contracts\Translation\TranslatorInterface;

class ConfigurationService
{
    /**
     * @var array
     */
    private $config = [];

    /**
     * @var UrlHelper
     */
    private $urlHelper;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        TranslatorInterface $translator,
        UrlHelper $urlHelper
    ) {
        $this->translator = $translator;
        $this->urlHelper = $urlHelper;
    }

    /**
     * @return void
     */
    public function setConfig(array $config)
    {
        $this->config = $config;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    public function getPaymentSessionTimeout(): string
    {
        return $this->config['payment_session_timeout'];
    }

    public function checkConfig(): void
    {
        foreach ($this->getPaymentProfiles() as $paymentProfile) {
            // Make sure the expressions are valid
            $paymentProfile->evaluatePspReturnUrlExpression('');
            $paymentProfile->evaluateReturnUrlExpression('');
            $paymentProfile->evaluateNotifyUrlExpression('');

            $type = $paymentProfile->getType();
            if ($this->getPaymentType($type) === null) {
                throw new \RuntimeException("Unknown payment type [$type]");
            }

            foreach ($this->getPaymentMethodsByType($type) as $paymentMethod) {
                $contract = $paymentMethod->getContract();
                if ($this->getPaymentContract($contract) === null) {
                    throw new \RuntimeException("Unknown contract [$contract]");
                }
            }
        }
    }

    /**
     * @return PaymentProfile[]
     */
    public function getPaymentProfiles(): array
    {
        $paymentProfiles = [];
        foreach ($this->config['payment_profiles'] as $paymentProfileConfig) {
            $paymentProfile = PaymentProfile::fromConfig($paymentProfileConfig);
            $paymentProfiles[] = $paymentProfile;
        }

        return $paymentProfiles;
    }

    public function getPaymentProfileByType(string $type): ?PaymentProfile
    {
        foreach ($this->getPaymentProfiles() as $paymentProfile) {
            if ($paymentProfile->getType() === $type) {
                return $paymentProfile;
            }
        }

        return null;
    }

    /**
     * @return PaymentMethod[]
     */
    public function getPaymentMethodsByType(string $type): array
    {
        $paymentMethods = [];

        foreach ($this->config['payment_profiles'] as $paymentProfileConfig) {
            if ($paymentProfileConfig['type'] !== $type) {
                continue;
            }

            $paymentMethodsConfig = $paymentProfileConfig['payment_methods'];
            foreach ($paymentMethodsConfig as $paymentMethodConfig) {
                if (array_key_exists('name', $paymentMethodConfig)) {
                    $paymentMethodConfig['name'] = $this->translator->trans($paymentMethodConfig['name']);
                }
                if ($paymentMethodConfig['demo_mode']) {
                    $paymentMethodConfig['name'] .= ' (DEMO)';
                }
                if (array_key_exists('image', $paymentMethodConfig)) {
                    $paymentMethodConfig['image'] = $this->urlHelper->getAbsoluteUrl($paymentMethodConfig['image']);
                }
                $paymentMethod = PaymentMethod::fromConfig($paymentMethodConfig);
                $paymentMethods[] = $paymentMethod;
            }
        }

        return $paymentMethods;
    }

    public function getPaymentMethodByTypeAndPaymentMethod(string $type, string $paymentMethod): ?PaymentMethod
    {
        foreach ($this->getPaymentMethodsByType($type) as $paymentMethodObject) {
            if ($paymentMethodObject->getIdentifier() === $paymentMethod) {
                return $paymentMethodObject;
            }
        }

        return null;
    }

    public function getPaymentContractByTypeAndPaymentMethod(string $type, string $paymentMethod): ?PaymentContract
    {
        $paymentMethodObject = $this->getPaymentMethodByTypeAndPaymentMethod($type, $paymentMethod);
        if ($paymentMethodObject !== null) {
            return $this->getPaymentContract($paymentMethodObject->getContract());
        }

        return null;
    }

    /**
     * Returns a payment contract by name.
     */
    public function getPaymentType(string $type): ?PaymentType
    {
        $paymentTypesConfig = $this->config['payment_types'];
        if (array_key_exists($type, $paymentTypesConfig)) {
            return PaymentType::fromConfig($type, $paymentTypesConfig[$type]);
        }

        return null;
    }

    /**
     * Returns a payment contract by name.
     */
    public function getPaymentContract(string $contract): ?PaymentContract
    {
        $paymentContractsConfig = $this->config['payment_contracts'];
        if (array_key_exists($contract, $paymentContractsConfig)) {
            return PaymentContract::fromConfig($contract, $paymentContractsConfig[$contract]);
        }

        return null;
    }

    /**
     * Returns all configured payment contracts.
     *
     * @return array<PaymentContract>
     */
    public function getPaymentContracts(): array
    {
        $contracts = [];
        $paymentContractsConfig = $this->config['payment_contracts'];
        foreach ($paymentContractsConfig as $paymentContractIdentifier => $paymentContractConfig) {
            $paymentContract = PaymentContract::fromConfig($paymentContractIdentifier, $paymentContractConfig);
            $contracts[] = $paymentContract;
        }

        return $contracts;
    }

    /**
     * ISO duration after a payment has expired after which the payment can be considered for cleanup.
     * Returns null if no cleanup is wanted.
     */
    public function getCleanupTimeout(string $paymentStatus): ?string
    {
        $cleanupConfigs = $this->config['cleanup'];
        foreach ($cleanupConfigs as $cleanupConfig) {
            if ($paymentStatus === $cleanupConfig['payment_status']) {
                return $cleanupConfig['timeout_before'];
            }
        }

        return null;
    }
}
