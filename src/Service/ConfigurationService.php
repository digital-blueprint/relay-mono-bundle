<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Service;

use Dbp\Relay\MonoBundle\Entity\PaymentContract;
use Dbp\Relay\MonoBundle\Entity\PaymentMethod;
use Dbp\Relay\MonoBundle\Entity\PaymentType;

class ConfigurationService
{
    /**
     * @var array
     */
    private $config = [];

    /**
     * @param array $config
     * @return void
     */
    public function setConfig(array $config)
    {
        $this->config = $config;
    }

    /**
     * @param string $type
     * @return PaymentType|null
     */
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
     * @param string $type
     * @return PaymentMethod[]
     */
    public function getPaymentMethodsByType(string $type): array
    {
        $paymentMethods = [];

        if (array_key_exists($type, $this->config['payment_types'])) {
            $paymentContractsConfig = $this->config['payment_types'][$type]['payment_contracts'];
            foreach ($paymentContractsConfig as $paymentContractConfig) {
                $paymentMethodsConfig = $paymentContractConfig['payment_methods'];
                foreach ($paymentMethodsConfig as $paymentMethodConfig) {
                    $paymentMethod = PaymentMethod::fromConfig($paymentMethodConfig);
                    $paymentMethods[] = $paymentMethod;
                }
            }
        }

        return $paymentMethods;
    }

    /**
     * @param string $type
     * @param string $paymentMethod
     * @return PaymentContract|null
     */
    public function getPaymentContractByTypeAndPaymentMethod(string $type, string $paymentMethod): ?PaymentContract
    {
        $paymentContract = null;

        if (array_key_exists($type, $this->config['payment_types'])) {
            $paymentContractsConfig = $this->config['payment_types'][$type]['payment_contracts'];
            foreach ($paymentContractsConfig as $paymentContractIdentifier => $paymentContractConfig) {
                $paymentMethodsConfig = $paymentContractConfig['payment_methods'];
                foreach ($paymentMethodsConfig as $paymentMethodConfig) {
                    $paymentMethod = PaymentMethod::fromConfig($paymentMethodConfig);
                    if ($paymentMethod->getIdentifier() === $paymentMethod) {
                        $paymentContract = PaymentContract::fromConfig($paymentContractIdentifier, $paymentContractConfig);
                    }
                }
            }
        }

        return $paymentContract;
    }
}
