<?php

namespace Xincheng\Launcher;

use InvalidArgumentException;
use Xincheng\Launcher\Request\RequestContract;
use Xincheng\Launcher\Service\ServiceManager;
use Xincheng\Launcher\Service\CircuitBreaker;

/**
 * 发射器
 *
 * 解决多语言微服务环境服务调用问题
 *
 * @author morgan
 * @since  2023-06-13 9:41
 */
class Launcher
{

    /**
     * @var ServiceManager 服务管理
     */
    private ServiceManager $serviceManager;

    /**
     * @var CacheInterface|null 缓存
     */
    private ?CacheInterface $cache = null;

    /**
     * @var CircuitBreaker|null 服务熔断器
     */
    private ?CircuitBreaker $circuitBreaker = null;

    /**
     * @var array 服务配置信息
     */
    private array $properties = [];

    public function __construct(array $properties = [], ?CacheInterface $cache = null)
    {
        $this->cache = $cache;
        $this->configuration($properties);
    }

    /**
     * 服务调用
     *
     * @param RequestContract|object $request 服务调用
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
    public function loadService(array $services, $circuitBreaker = null)
    {
        $this->serviceManager = ServiceManager::load($services, $circuitBreaker);
        $this->properties['services'] = $services;
    }

    /**
     * 加载熔断器
     *
     * @return void
     */
    public function loadCircuitBreaker(array $circuitBreakerConfig, $adapterRedis)
    {
        $this->circuitBreaker = !empty($circuitBreakerConfig['open']) && $adapterRedis
            ? new CircuitBreaker($circuitBreakerConfig, $adapterRedis)
            : null;
    }

    /**
     * 配置初始化
     *
     * @param array $properties 配置信息
     * @return void
     */
    public function configuration(array $properties)
    {
        $this->configureServices($properties, $this->cache);
        $this->properties = $properties;
    }

    private function configureServices(array $properties, ?CacheInterface $cache): void
    {
        $config = $properties['circuitbreaker'] ?? [];
        $redis = null;
        if (!empty($config['open']) && $cache !== null
            && method_exists($cache, 'getRedis') && is_callable([$cache, 'getRedis'])) {
            $redis = $cache->getRedis();
        }
        $this->loadCircuitBreaker($config, $redis);
        $this->loadService($properties['services'] ?? [], $this->circuitBreaker);
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

        $handle->setCache($this->cache);

        return $handle->handle($request, $this->properties);
    }

    /**
     * 设置缓存
     *
     * @param CacheInterface $cache
     */
    public function setCache(CacheInterface $cache): void
    {
        if ($this->cache === $cache) {
            return;
        }
        if (!empty($this->properties['circuitbreaker']['open'])) {
            $this->configureServices($this->properties, $cache);
        }
        $this->cache = $cache;
    }

}
