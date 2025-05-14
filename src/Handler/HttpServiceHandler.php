<?php

namespace Xincheng\Launcher\Handler;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
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

    public ?CircuitBreaker $circuitbreaker = null;


    public function __construct($circuitBreaker)
    {
        if($circuitBreaker instanceof CircuitBreaker){
            $this->circuitbreaker = $circuitBreaker;
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
        $serviceName = $this->getServiceName($request,$url);
        $this->initCustomCircuitBreaker($request);
        if($this->getCircuitBreaker()){
            try{
                $client   = new Client([
                    'handler' => $this->circuitbreaker->getClientHandlersWithGuzzleMiddleware(),
                    'ganesha.service_name' => $serviceName
                ]);
                //开放自定义
                $request->before($client);
                //头信息设置
                $this->headers($request, $options);
                return  $client->request($request->method(), $url, $options);
            } catch (\Ackintosh\Ganesha\Exception\RejectedException $e) {
                if($request->isCircuitBreakerProcess()){
                    return $request->fallback();
                }else{
                    throw new ServiceRejectedException("Service temporarily unavailable");
                }
            } catch (GuzzleException $e){
                $this->circuitbreaker->getGanesha()->failure($serviceName);
                throw new ClientException($e->getMessage(), $e->getRequest(), $e->getResponse(), $e , $e->getHandlerContext());
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

    private function initCustomCircuitBreaker(RequestContract $request): void
    {
        $circuitBreakerConfig = $request->circuitBreakerConfig();
        if($circuitBreakerConfig && isset($circuitBreakerConfig['open']) &&
            $circuitBreakerConfig['open'] && $this->cache && $this->cache->getRedis()){
            $this->circuitbreaker = new CircuitBreaker($circuitBreakerConfig,$this->cache->getRedis());
        }
    }

    private function getServiceName(RequestContract $request,string $url): string
    {
        return $request->method(). $url;
    }

    protected function getCircuitBreaker(): ?CircuitBreaker
    {
        return $this->circuitbreaker;
    }

}