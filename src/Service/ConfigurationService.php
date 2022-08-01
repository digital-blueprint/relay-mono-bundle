<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Service;

use Dbp\Relay\MonoBundle\Entity\PaymentContract;
use Dbp\Relay\MonoBundle\Entity\PaymentMethod;
use Dbp\Relay\MonoBundle\Entity\PaymentType;
use Symfony\Component\HttpFoundation\UrlHelper;

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

    public function __construct(
        UrlHelper $urlHelper
    )
    {
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
            $paymentContractsConfig = $this->config['payment_types'][$type]['payment_contracts'];
            foreach ($paymentContractsConfig as $paymentContractConfig) {
                $paymentMethodsConfig = $paymentContractConfig['payment_methods'];
                foreach ($paymentMethodsConfig as $paymentMethodConfig) {
                    if (array_key_exists('image', $paymentMethodConfig)) {
                        $paymentMethodConfig['image'] = $this->urlHelper->getAbsoluteUrl($paymentMethodConfig['image']);
                    }
                    $paymentMethod = PaymentMethod::fromConfig($paymentMethodConfig);
                    $paymentMethods[] = $paymentMethod;
                }
            }
        }

        return $paymentMethods;
    }

    public function getPaymentContractByTypeAndPaymentMethod(string $type, string $paymentMethod): ?PaymentContract
    {
        $paymentContract = null;

        if (array_key_exists($type, $this->config['payment_types'])) {
            $paymentContractsConfig = $this->config['payment_types'][$type]['payment_contracts'];
            foreach ($paymentContractsConfig as $paymentContractIdentifier => $paymentContractConfig) {
                $paymentMethodsConfig = $paymentContractConfig['payment_methods'];
                foreach ($paymentMethodsConfig as $paymentMethodConfig) {
                    $paymentMethodObject = PaymentMethod::fromConfig($paymentMethodConfig);
                    if ($paymentMethodObject->getIdentifier() === $paymentMethod) {
                        $paymentContract = PaymentContract::fromConfig($paymentContractIdentifier, $paymentContractConfig);
                        break 2;
                    }
                }
            }
        }

        return $paymentContract;
    }
}
