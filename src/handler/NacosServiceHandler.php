<?php

namespace Launcher\Handler;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use Launcher\Exception\ServiceNotFoundException;
use Launcher\request\RequestContract;
use Launcher\Service\ServiceConstant;
use Psr\Http\Message\ResponseInterface;

/**
 * Nacos服务处理器
 *
 * @author morgan
 * @since 2023-06-13 14:19
 */
class NacosServiceHandler implements ServiceHandler
{

    /**
     * @var string nacos api 服务列表
     */
    private static string $API_SERVICE_LIST = '/nacos/v1/ns/instance/list?';

    /**
     * 执行Nacos服务调用
     *
     * @return object 执行结果
     * @throws GuzzleException
     */
    public function handle(RequestContract $request, array $properties)
    {
        //获取服务实例列表
        $services = $this->serviceInstants($request->server(), $properties);

        if (empty($services)) {
            throw new ServiceNotFoundException("当前无服务可用,请检查服务名称或服务资源");
        }

        $node = $this->selectService($services);

        //请求服务
        return $this->execute($request, $node);
    }

    public function type(): string
    {
        return ServiceConstant::$NACOS_SERVICE;
    }

    /**
     * 获取服务列表
     *
     * @param string $name 服务名称
     * @param array $properties 配置信息
     * @return array 节点信息
     * @throws GuzzleException http请求异常
     */
    protected function serviceInstants(string $name, array $properties): array
    {
        $config = $properties['nacos'];

        if (empty($config)) {
            throw new InvalidArgumentException("请检查配置是否包含nacos配置");
        }

        $client = new Client();
        $response = $client->get($config['host'] . self::$API_SERVICE_LIST . $this->queryServiceParameters($name));
        $result = json_decode((string)$response->getBody());

        return $result->hosts;
    }

    /**
     * 构建查村服务参数
     *
     * @param string $name
     * @return string 查询服务参数
     */
    protected function queryServiceParameters(string $name): string
    {
        $query = [
            'serviceName' => $name
        ];

        if (!empty($config['groupName'])) {
            $query['groupName'] = $config['groupName'];
        }

        if (!empty($config['namespaceId'])) {
            $query['namespaceId'] = $config['namespaceId'];
        }

        return http_build_query($query);
    }

    /**
     * 选择服务
     *
     * @param array $services 服务
     * @return object 服务节点信息
     */
    protected function selectService(array $services): object
    {
        shuffle($services);

        return array_pop($services);
    }

    /**
     * 执行请求
     *
     * @param RequestContract $request 请求参数
     * @param object $node 服务节点
     * @return ResponseInterface 相应信息
     * @throws GuzzleException
     */
    protected function execute(RequestContract $request, object $node): ResponseInterface
    {
        $url = $node->ip . ':' . $node->port . $request->router();
        $client = new Client();

        return $client->request($request->method(), $url, $request->options());
    }
}