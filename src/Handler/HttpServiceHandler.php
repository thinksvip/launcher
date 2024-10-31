<?php

namespace Xincheng\Launcher\Handler;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Xincheng\Launcher\Request\RequestContract;
use Xincheng\Launcher\Service\ServiceConstant;
use Xincheng\Launcher\Service\CircuitBreaker;

/**
 * 静态服务处理器
 *
 * @author morgan
 * @since  2023-06-13 14:19
 */
class HttpServiceHandler extends BaseServiceHandler
{
    use HandlerHelper;
    public bool $circuitbreakerOpen = false;

    /**
     * 处理http服务请求
     *
     * @param RequestContract $request    请求信息
     * @param array           $properties 配置信息
     * @throws GuzzleException http请求异常
     */
    public function handle(RequestContract $request, array $properties)
    {
        $this->circuitbreakerOpen = (isset($properties['circuitbreaker']['open']) && $properties['circuitbreaker']['open']);
        $options = $request->options();
        $service = $this->getService($request->server(), $properties);
        $target  = $this->getTarget($service['target']);
        $url = $target . $request->router();

        if (!empty($request->params())) {
            $url .= "?" . $request->buildQuery();
        }
        if($this->circuitbreakerOpen){
            try{
                $handlers = (new CircuitBreaker($properties['circuitbreaker']))->getClientHandlersWithGuzzleMiddleware();
                $client   = new Client([
                    'handler' => $handlers,
                    'ganesha.service_name' => $request->method(). $url
                ]);
                //开放自定义
                $request->before($client);
                //头信息设置
                $this->headers($request, $options);
                return $client->request($request->method(), $url, $options);
            } catch (\Ackintosh\Ganesha\Exception\RejectedException $e) {
                if (is_callable([$request,'fallback'])){
                    return $request->fallback();
                }else{
                    throw  new \Exception("Service temporarily unavailable");
                }
            }
        }else{
            $client = new Client();
            $request->before($client);
            $this->headers($request, $options);
            return $client->request($request->method(), $url, $options);
        }
    }

    public function type(): string
    {
        return ServiceConstant::$HTTP_SERVICE;
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