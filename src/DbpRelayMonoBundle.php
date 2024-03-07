<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle;

use Dbp\Relay\MonoBundle\BackendServiceProvider\BackendServiceCompilerPass;
use Dbp\Relay\MonoBundle\PaymentServiceProvider\PaymentServiceProviderServiceCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class DbpRelayMonoBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        BackendServiceCompilerPass::register($container);
        PaymentServiceProviderServiceCompilerPass::register($container);
    }
}
