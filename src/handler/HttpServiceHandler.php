<?php

namespace Launcher\Handler;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Launcher\Exception\ServiceNotFoundException;
use Launcher\request\RequestContract;
use Launcher\Service\ServiceConstant;

/**
 * 静态服务处理器
 *
 * @author morgan
 * @since 2023-06-13 14:19
 */
class HttpServiceHandler implements ServiceHandler
{

    /**
     * 处理http服务请求
     *
     * @param RequestContract $request 请求信息
     * @param array $properties 配置信息
     * @throws GuzzleException
     */
    public function handle(RequestContract $request, array $properties)
    {
        $client = new Client();
        $service = $this->getService($request->server(), $properties);
        $target = $this->getTarget($service['target']);
        $url = $target . $request->router();

        return $client->request($request->method(), $url, $request->options());
    }

    public function type(): string
    {
        return ServiceConstant::$HTTP_SERVICE;
    }

    /**
     * 获取服务名称
     *
     * @param string $name 服务名称
     * @param array $properties 配置信息
     * @return array 服务信息
     */
    protected function getService(string $name, array $properties): array
    {
        $services = $properties['services'];

        foreach ($services as $item) {
            if ($item['name'] == $name) {
                return $item;
            }
        }

        throw new ServiceNotFoundException("服务未找到,请检查配置文件");
    }

    /**
     * 获取目标地址
     *
     * @param array $targets 目标地址集合
     * @return string
     */
    protected function getTarget(array $targets): string
    {
        shuffle($targets);

        return array_pop($targets);
    }
}