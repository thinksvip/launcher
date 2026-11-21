<?php

namespace Xincheng\Launcher\Tests;

use Xincheng\Launcher\Request\WebBaseRequest;

class ServiceRequest extends WebBaseRequest
{
    private string $service;
    private string $route;

    public function __construct(string $service, string $route)
    {
        $this->service = $service;
        $this->route = $route;
    }

    public function server(): string
    {
        return $this->service;
    }

    public function router(): string
    {
        return $this->route;
    }

    public function method(): string
    {
        return 'GET';
    }
}
