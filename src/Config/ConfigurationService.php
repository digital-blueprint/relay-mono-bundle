<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Config;

use Symfony\Component\HttpFoundation\UrlHelper;
use Symfony\Contracts\Translation\TranslatorInterface;

class ConfigurationService
{
    /**
     * @var mixed[]
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
        UrlHelper $urlHelper,
    ) {
        $this->translator = $translator;
        $this->urlHelper = $urlHelper;
    }

    /**
     * @param mixed[] $config
     */
    public function setConfig(array $config): void
    {
        $this->config = $config;
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
     * @param mixed[] $paymentMethodConfig
     *
     * @return mixed[]
     */
    private function adjustPaymentMethodConfig(array $paymentMethodConfig): array
    {
        $paymentMethodConfig['name'] = $this->translator->trans($paymentMethodConfig['name'], domain: 'dbp_relay_mono');
        if ($paymentMethodConfig['demo_mode']) {
            $paymentMethodConfig['name'] .= ' (DEMO)';
        }

        if ($paymentMethodConfig['image'] !== null) {
            $paymentMethodConfig['image'] = $this->urlHelper->getAbsoluteUrl($paymentMethodConfig['image']);
        }

        return $paymentMethodConfig;
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
            foreach ($paymentMethodsConfig as $identifier => $paymentMethodConfig) {
                $paymentMethodConfig = $this->adjustPaymentMethodConfig($paymentMethodConfig);
                $paymentMethod = PaymentMethod::fromConfig($identifier, $paymentMethodConfig);
                $paymentMethods[] = $paymentMethod;
            }
        }

        return $paymentMethods;
    }

    public function getPaymentMethodByTypeAndPaymentMethod(string $type, string $paymentMethod): ?PaymentMethod
    {
        if (array_key_exists($type, $this->config['payment_types'])) {
            $paymentMethodsConfig = $this->config['payment_types'][$type]['payment_methods'];
            foreach ($paymentMethodsConfig as $identifier => $paymentMethodConfig) {
                $paymentMethodConfig = $this->adjustPaymentMethodConfig($paymentMethodConfig);
                $paymentMethodObject = PaymentMethod::fromConfig($identifier, $paymentMethodConfig);
                if ($paymentMethodObject->getIdentifier() === $paymentMethod) {
                    return $paymentMethodObject;
                }
            }
        }

        return null;
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

    public function createJsonForMethods(string $type): string
    {
        $paymentMethods = $this->getPaymentMethodsByType($type);
        $payload = [];
        foreach ($paymentMethods as $paymentMethod) {
            $payload[] = [
                'identifier' => $paymentMethod->getIdentifier(),
                'name' => $paymentMethod->getName(),
                'image' => $paymentMethod->getImage(),
            ];
        }

        return json_encode($payload, JSON_THROW_ON_ERROR);
    }
}
