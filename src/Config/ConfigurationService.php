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
        foreach ($this->getPaymentTypes() as $paymentType) {
            // Make sure the expressions are valid
            $paymentType->evaluatePspReturnUrlExpression('');
            $paymentType->evaluateReturnUrlExpression('');
            $paymentType->evaluateNotifyUrlExpression('');
        }
    }

    /**
     * @return PaymentType[]
     */
    public function getPaymentTypes(): array
    {
        $paymentTypes = [];

        $paymentTypesConfig = $this->config['payment_types'];
        foreach ($paymentTypesConfig as $type => $paymentTypeConfig) {
            $paymentType = PaymentType::fromConfig($type, $paymentTypeConfig);
            $paymentTypes[] = $paymentType;
        }

        return $paymentTypes;
    }

    public function getPaymentTypeByType(string $type): ?PaymentType
    {
        $paymentType = null;

        if (array_key_exists($type, $this->config['payment_types'])) {
            $paymentTypeConfig = $this->config['payment_types'][$type];
            $paymentType = PaymentType::fromConfig($type, $paymentTypeConfig);
        }

        return $paymentType;
    }

    /**
     * @return PaymentMethod[]
     */
    public function getPaymentMethodsByType(string $type): array
    {
        $paymentMethods = [];

        if (array_key_exists($type, $this->config['payment_types'])) {
            $paymentTypeConfig = $this->config['payment_types'][$type];
            $paymentMethodsConfig = $paymentTypeConfig['payment_methods'];
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
        if (array_key_exists($type, $this->config['payment_types'])) {
            $paymentMethodsConfig = $this->config['payment_types'][$type]['payment_methods'];
            foreach ($paymentMethodsConfig as $paymentMethodConfig) {
                $paymentMethodObject = PaymentMethod::fromConfig($paymentMethodConfig);
                if ($paymentMethodObject->getIdentifier() === $paymentMethod) {
                    return $paymentMethodObject;
                }
            }
        }

        return null;
    }

    public function getPaymentContractByTypeAndPaymentMethod(string $type, string $paymentMethod): ?PaymentContract
    {
        if (array_key_exists($type, $this->config['payment_types'])) {
            $paymentMethodsConfig = $this->config['payment_types'][$type]['payment_methods'];
            foreach ($paymentMethodsConfig as $paymentMethodConfig) {
                $paymentMethodObject = PaymentMethod::fromConfig($paymentMethodConfig);
                if ($paymentMethodObject->getIdentifier() === $paymentMethod) {
                    return $this->getPaymentContract($type, $paymentMethodObject->getContract());
                }
            }
        }

        return null;
    }

    /**
     * Returns a payment contract by name.
     */
    public function getPaymentContract(string $type, string $contract): ?PaymentContract
    {
        if (array_key_exists($type, $this->config['payment_types'])) {
            $paymentTypeConfig = $this->config['payment_types'][$type];
            $paymentContractsConfig = $paymentTypeConfig['payment_contracts'];
            if (array_key_exists($contract, $paymentContractsConfig)) {
                return PaymentContract::fromConfig($contract, $paymentContractsConfig[$contract]);
            }
        }

        return null;
    }

    /**
     * Returns all configured payment contracts.
     *
     * @return array<PaymentContract>
     */
    public function getPaymentContracts(string $type): array
    {
        $contracts = [];
        if (array_key_exists($type, $this->config['payment_types'])) {
            $paymentContractsConfig = $this->config['payment_types'][$type]['payment_contracts'];
            foreach ($paymentContractsConfig as $paymentContractIdentifier => $paymentContractConfig) {
                $paymentContract = PaymentContract::fromConfig($paymentContractIdentifier, $paymentContractConfig);
                $contracts[] = $paymentContract;
            }
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
