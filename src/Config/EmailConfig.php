<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Config;

class EmailConfig
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function getDsn(): string
    {
        return $this->config['dsn'];
    }

    public function getFrom(): string
    {
        return $this->config['from'];
    }

    public function getTo(): string
    {
        return $this->config['to'];
    }

    public function getSubject(): string
    {
        return $this->config['subject'];
    }

    public function getHtmlTemplate(): string
    {
        return $this->config['html_template'];
    }
}
