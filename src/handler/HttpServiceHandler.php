<?php

namespace Xincheng\Launcher\Handler;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Xincheng\Launcher\Exception\ServiceNotFoundException;
use Xincheng\Launcher\request\RequestContract;
use Xincheng\Launcher\Service\ServiceConstant;
use Xincheng\Launcher\utils\ArrUtils;

/**
 * 静态服务处理器
 *
 * @author morgan
 * @since  2023-06-13 14:19
 */
class HttpServiceHandler extends BaseServiceHandler
{
    use HandlerHelper;

    /**
     * 处理http服务请求
     *
     * @param RequestContract $request    请求信息
     * @param array           $properties 配置信息
     * @throws GuzzleException http请求异常
     */
    public function handle(RequestContract $request, array $properties)
    {
        $client = new Client();
        $options = $request->options();
        $service = $this->getService($request->server(), $properties);
        $target = $this->getTarget($service['target']);
        $url = $target . $request->router();

        if (!empty($request->params())) {
            $url .= "?" . $request->buildQuery();
        }

        //开放自定义
        $request->before($client);

        //头信息设置
        $this->headers($request, $options);

        return $client->request($request->method(), $url, $options);
    }

    public function type(): string
    {
        return ServiceConstant::$HTTP_SERVICE;
    }

    /**
     * 获取服务名称
     *
     * @param string $name       服务名称
     * @param array  $properties 配置信息
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