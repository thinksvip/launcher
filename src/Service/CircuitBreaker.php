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
     * @var string 适配器redis host 也可以使用其他适配器 这里就使用redis
     */
    public string $adapterRedisHost = '127.0.0.1';
    public int $adapterRedisPort = 6379;
     /**
     * @var string redis 认证 requirepass
     */
    public string $adapterRedisAuth = '';
    /**
     * @var int 时间窗口内失败率 百分之
     */
    public int $failureRateThreshold = 50 ;

    public int $intervalToHalfOpen = 5 ;
    /**
     * @var int 时间窗口内最小请求数量
     */
    public int $minimumRequests = 10;
    /**
     * @var int 统计时间窗口 单位s
     */
    public int $timeWindow = 60 ;

    public function __construct(array $config = [])
    {
        isset($config['failureRateThreshold']) && $this->failureRateThreshold = $config['failureRateThreshold'];
        isset($config['intervalToHalfOpen']) && $this->intervalToHalfOpen = $config['intervalToHalfOpen'];
        isset($config['minimumRequests']) && $this->minimumRequests = $config['minimumRequests'];
        isset($config['minimumRequests']) && $this->failureRateThreshold = $config['minimumRequests'];
        isset($config['redisHost']) && $this->adapterRedisHost = $config['redisHost'];
        isset($config['redisPort']) && $this->adapterRedisPort = $config['redisPort'];
        isset($config['redisAuth']) && $this->adapterRedisAuth = $config['redisAuth'];
        $redis = new \Redis();
        $redis->connect( $this->adapterRedisHost,$this->adapterRedisPort);
        $redis->auth( $this->adapterRedisAuth);
        //redis适配器 暂时只支持这一种
        $adapter = new \Ackintosh\Ganesha\Storage\Adapter\Redis($redis);
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