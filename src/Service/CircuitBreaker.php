<?php

namespace Xincheng\Launcher\Service;

use Ackintosh\Ganesha;
use Ackintosh\Ganesha\Builder;
use Ackintosh\Ganesha\Exception\RejectedException;
use Ackintosh\Ganesha\GuzzleMiddleware\ServiceNameExtractor;
use Ackintosh\Ganesha\Storage\Adapter\Redis;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\RejectedPromise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * 服务熔断器
 *
 * @author vaporChen
 * @since  2024-10-30
 */
class CircuitBreaker
{
    private Ganesha $ganesha;
    public int $timeWindow = 60;
    public int $failureRateThreshold = 50;
    public int $intervalToHalfOpen = 5;
    public int $minimumRequests = 10;

    public function __construct(array $config, $adapterRedis)
    {
        $this->timeWindow = $config['timeWindow'] ?? $this->timeWindow;
        $this->failureRateThreshold = $config['failureRateThreshold'] ?? $this->failureRateThreshold;
        $this->intervalToHalfOpen = $config['intervalToHalfOpen'] ?? $this->intervalToHalfOpen;
        $this->minimumRequests = $config['minimumRequests'] ?? $this->minimumRequests;

        $this->ganesha = Builder::withRateStrategy()
            ->adapter(new Redis($adapterRedis))
            ->failureRateThreshold($this->failureRateThreshold)
            ->intervalToHalfOpen($this->intervalToHalfOpen)
            ->minimumRequests($this->minimumRequests)
            ->timeWindow($this->timeWindow)
            ->build();
    }

    /**
     * Ganesha 2.x 自带的中间件依赖 Promises 2 已移除的函数。
     * 通过各版本通用的 Ganesha 和 Promise 接口登记请求结果。
     */
    public function getGuzzleMiddleware(): callable
    {
        $extractor = new ServiceNameExtractor();
        return function (callable $handler) use ($extractor) {
            return function (RequestInterface $request, array $options) use ($handler, $extractor) {
                $service = $extractor->extract($request, $options);
                if (!$this->ganesha->isAvailable($service)) {
                    $error = method_exists(RejectedException::class, 'withServiceName')
                        ? RejectedException::withServiceName($service)
                        : new RejectedException(sprintf('"%s" is not available', $service));
                    return new RejectedPromise($error);
                }

                try {
                    $promise = $handler($request, $options);
                } catch (TransferException $e) {
                    $promise = new RejectedPromise($e);
                }

                return $promise->then(
                    function (ResponseInterface $response) use ($service) {
                        if ($response->getStatusCode() >= 500) {
                            $this->ganesha->failure($service);
                        } else {
                            $this->ganesha->success($service);
                        }
                        return $response;
                    },
                    function ($reason) use ($service) {
                        // 自定义 HandlerStack 可能已将 4xx 响应转换为 ClientException。
                        if ($reason instanceof ClientException) {
                            $this->ganesha->success($service);
                        } else {
                            $this->ganesha->failure($service);
                        }
                        return new RejectedPromise($reason);
                    }
                );
            };
        };
    }

    public function getClientHandlersWithGuzzleMiddleware(): HandlerStack
    {
        $handlers = HandlerStack::create();
        $handlers->unshift($this->getGuzzleMiddleware());
        return $handlers;
    }
}
