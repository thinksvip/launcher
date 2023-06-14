<?php

namespace Launcher;

use InvalidArgumentException;
use Launcher\Request\RequestContract;
use Launcher\Service\ServiceManager;

/**
 * 发射器
 *
 * 解决多语言微服务环境服务调用问题
 *
 * @author morgan
 * @since 2023-06-13 9:41
 */
class Launcher
{

    /**
     * @var ServiceManager 服务管理
     */
    private ServiceManager $serviceManager;

    /**
     * @var array 服务配置信息
     */
    private array $properties = [

    ];

    public function __construct(array $properties)
    {
        $this->configuration($properties);
    }

    /**
     * 服务调用
     *
     * @param RequestContract $request 服务调用
     * @return mixed
     */
    public function run(RequestContract $request)
    {
        //获取服务
        return $this->discover($request);
    }

    /**
     * 加载服务
     *
     * @return void
     */
    public function loadService(array $services)
    {
        $this->serviceManager = ServiceManager::load($services);
    }

    /**
     * 配置初始化
     *
     * @param array $properties 配置信息
     * @return void
     */
    public function configuration(array $properties)
    {
        $this->properties = $properties;

        //加载服务
        $this->loadService($properties['services']);
    }

    /**
     * 匹配服务
     *
     * @param RequestContract $request
     * @return mixed
     */
    public function discover(RequestContract $request)
    {
        $server = $request->server();

        if (empty($server)) {
            throw new InvalidArgumentException("无效的服务参数");
        }

        $handle = $this->serviceManager->match($server);

        //处理服务
        return $handle->handle($request, $this->properties);
    }
}