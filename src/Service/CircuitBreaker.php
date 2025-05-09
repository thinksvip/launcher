<?php

namespace Xincheng\Launcher\Service;
use Ackintosh\Ganesha;
use GuzzleHttp\HandlerStack;

/**
 * 服务熔断器
 *
 * @author vaporChen
 * @since  2024-10-30
 */
class CircuitBreaker
{
    private  Ganesha  $ganesha;
    /**
     * @var int 统计时间窗口 单位s
     */
    public int $timeWindow = 60 ;
    /**
     * @var int 时间窗口内失败率 百分之
     */
    public int $failureRateThreshold = 50 ;

    public int $intervalToHalfOpen = 5 ;
    /**
     * @var int 时间窗口内最小请求数量
     */
    public int $minimumRequests = 10;

    public function __construct(array $config = [], $adapterRedis)
    {
        isset($config['failureRateThreshold']) && $this->failureRateThreshold = $config['failureRateThreshold'];
        isset($config['intervalToHalfOpen']) && $this->intervalToHalfOpen = $config['intervalToHalfOpen'];
        isset($config['minimumRequests']) && $this->minimumRequests = $config['minimumRequests'];
        isset($config['minimumRequests']) && $this->failureRateThreshold = $config['minimumRequests'];   
        //redis适配器 暂时只支持这一种
        $adapter = new \Ackintosh\Ganesha\Storage\Adapter\Redis($adapterRedis);
        //配置熔断策略
        $this->ganesha = \Ackintosh\Ganesha\Builder::withRateStrategy()
            ->adapter($adapter)
            ->failureRateThreshold($this->failureRateThreshold)
            ->intervalToHalfOpen($this->intervalToHalfOpen)
            ->minimumRequests($this->minimumRequests)
            ->timeWindow($this->timeWindow)
            ->build();
    }

    /**
     * GuzzleClient 中间件
     */
    public function getClientHandlersWithGuzzleMiddleware(): HandlerStack
    {
        $middleware  = new \Ackintosh\Ganesha\GuzzleMiddleware($this->ganesha);
        $handlers    =  \GuzzleHttp\HandlerStack::create();
        $handlers->push($middleware);
        return $handlers;
    }

}