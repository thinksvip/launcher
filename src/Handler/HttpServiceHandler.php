<?php

namespace Xincheng\Launcher\Handler;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Xincheng\Launcher\Exception\ServiceRejectedException;
use Xincheng\Launcher\Request\RequestContract;
use Xincheng\Launcher\Service\CircuitBreaker;
use Xincheng\Launcher\Service\ServiceConstant;

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
    public $clientHandlers = null;

    public function __construct($circuitBreaker)
    {
        if($circuitBreaker instanceof CircuitBreaker){
            $this->circuitbreakerOpen = true;
            $this->clientHandlers = $circuitBreaker->getClientHandlersWithGuzzleMiddleware();
        }
    }

    /**
     * 处理http服务请求
     *
     * @param RequestContract $request    请求信息
     * @param array           $properties 配置信息
     * @throws GuzzleException|ServiceRejectedException http请求异常
     */
    public function handle(RequestContract $request, array $properties)
    {
        $options = $request->options();
        $service = $this->getService($request->server(), $properties);
        $target  = $this->getTarget($service['target']);
        $url = $target . $request->router();
        if (!empty($request->params())) {
            $url .= "?" . $request->buildQuery();
        }
        if($this->circuitbreakerOpen){
            try{
                $client   = new Client([
                    'handler' => $this->clientHandlers,
                    'ganesha.service_name' => $request->method(). $url
                ]);
                //开放自定义
                $request->before($client);
                //头信息设置
                $this->headers($request, $options);
                return $client->request($request->method(), $url, $options);
            } catch (\Ackintosh\Ganesha\Exception\RejectedException $e) {
                if($request->isCircuitBreakerProcess()){
                    return $request->fallback();
                }else{
                    throw  new ServiceRejectedException("Service temporarily unavailable");
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