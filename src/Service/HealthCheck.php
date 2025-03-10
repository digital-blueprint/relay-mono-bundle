<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Service;

use Dbp\Relay\CoreBundle\HealthCheck\CheckInterface;
use Dbp\Relay\CoreBundle\HealthCheck\CheckOptions;
use Dbp\Relay\CoreBundle\HealthCheck\CheckResult;
use Dbp\Relay\MonoBundle\Config\ConfigurationService;

class HealthCheck implements CheckInterface
{
    /**
     * @var PaymentService
     */
    private $payment;
    /**
     * @var ConfigurationService
     */
    private $configService;

    public function __construct(PaymentService $payment, ConfigurationService $configService)
    {
        $this->payment = $payment;
        $this->configService = $configService;
    }

    public function getName(): string
    {
        return 'mono';
    }

    /**
     * @param array<mixed> $args
     */
    private function checkMethod(string $description, callable $func, array $args = []): CheckResult
    {
        $result = new CheckResult($description);
        try {
            $func(...$args);
        } catch (\Throwable $e) {
            $result->set(CheckResult::STATUS_FAILURE, $e->getMessage(), ['exception' => $e]);

            return $result;
        }
        $result->set(CheckResult::STATUS_SUCCESS);

        return $result;
    }

    public function check(CheckOptions $options): array
    {
        $results[] = $this->checkMethod('Check bundle configuration', [$this->configService, 'checkConfig']);
        $results[] = $this->checkMethod('Check if we can connect to the DB', [$this->payment, 'checkConnection']);
        $results[] = $this->checkMethod('Check connector configuration', [$this->payment, 'checkConfig']);

        return $results;
    }
}
