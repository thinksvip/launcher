<?php

namespace Xincheng\Launcher\Handler;

use Ackintosh\Ganesha\Exception\RejectedException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Xincheng\Launcher\Exception\ServiceNotFoundException;
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
    private ?CircuitBreaker $circuitBreaker = null;

    public function __construct($circuitBreaker = null)
    {
        if ($circuitBreaker instanceof CircuitBreaker) {
            $this->circuitBreaker = $circuitBreaker;
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
        $url = $this->requestUrl($target, $request);
        $config = [];
        if ($this->circuitbreakerOpen) {
            $config = [
                'handler' => $this->clientHandlers,
                'ganesha.service_name' => $request->server(),
            ];
            if (isset($options['handler']) && $this->circuitBreaker !== null) {
                $middleware = $this->circuitBreaker->getGuzzleMiddleware();
                $options['handler'] = $middleware($options['handler']);
            }
        }

        $client = new Client($config);
        $request->before($client);
        $this->headers($request, $options);
        try {
            return $client->request($request->method(), $url, $options);
        } catch (RejectedException $e) {
            if (!$this->circuitbreakerOpen) {
                throw $e;
            }
            $publicMethods = array_map('strtolower', get_class_methods($request));
            if (in_array('iscircuitbreakerprocess', $publicMethods, true)
                && in_array('fallback', $publicMethods, true) && $request->isCircuitBreakerProcess()) {
                return $request->fallback();
            }
            throw new ServiceRejectedException("Service temporarily unavailable", 0, $e);
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
        $targets = array_filter($targets, static function ($target) {
            return is_string($target) && trim($target) !== '';
        });
        if (!$targets) {
            throw new ServiceNotFoundException("HTTP 服务未配置可用的目标地址");
        }
        shuffle($targets);

        return array_pop($targets);
    }

}
