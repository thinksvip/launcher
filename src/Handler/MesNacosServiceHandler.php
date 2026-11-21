<?php

namespace Xincheng\Launcher\Handler;

use AlibabaCloud\SDK\Mse\V20190531\Models\ListAnsInstancesRequest;
use AlibabaCloud\SDK\Mse\V20190531\Mse;
use Darabonba\OpenApi\Models\Config;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Xincheng\Launcher\Exception\ServiceNotFoundException;
use Xincheng\Launcher\Request\RequestContract;
use Xincheng\Launcher\Service\ServiceConstant;

/**
 * MesNacosServiceHandler
 *
 * @author: morgan
 * @slice : 2024-07-16 11:16:34
 */
class MesNacosServiceHandler extends BaseServiceHandler
{
    use HandlerHelper;

    /**
     * @throws GuzzleException
     */
    public function handle(RequestContract $request, array $properties): ResponseInterface
    {
        $config = $properties['mes_nacos'];

        //验证参数
        $this->checkParameter($config);

        //构建客户端
        $client = $this->createClient($config);

        //获取服务
        $service = $this->service($client, $config, $request);

        return $this->execute($request, $service['Ip'], $service['Port']);
    }

    public function type(): string
    {
        return ServiceConstant::$MES_NACOS_SERVICE;
    }

    public function service(Mse $client, array $config, RequestContract $request): array
    {
        $listInstancesReq = new ListAnsInstancesRequest([
            "serviceName" => $request->server(),
            "instanceId"  => $config['instance_id'],
            "namespaceId" => $config['namespace_id'],
            "pageNum"     => 1,
            "pageSize"    => 10,
        ]);

        do {
            $response = $client->listAnsInstances($listInstancesReq);
            $body = $response->body->toMap();
            $nodes = array_filter($body['Data'] ?? [], static function ($node) {
                return !empty($node['Ip']) && !empty($node['Port'])
                    && ($node['Healthy'] ?? true) && ($node['Enabled'] ?? true);
            });
            if ($nodes) {
                return $this->selectService($nodes);
            }
            $pageSize = ($body['PageSize'] ?? 0) > 0 ? $body['PageSize'] : $listInstancesReq->pageSize;
            $readCount = $listInstancesReq->pageNum * $pageSize;
            $listInstancesReq->pageNum++;
        } while (!empty($body['Data']) && $readCount < ($body['TotalCount'] ?? 0));

        throw new ServiceNotFoundException("当前无服务可用,请检查服务名称或服务资源");
    }

    /**
     * 执行请求
     *
     * @param RequestContract $request 请求参数
     * @param string          $ip      服务ip
     * @param string          $port    端口
     * @return ResponseInterface 相应信息
     * @throws GuzzleException 请求异常
     */
    protected function execute(RequestContract $request, string $ip, string $port): ResponseInterface
    {
        $url = $this->requestUrl($ip . ':' . $port, $request);
        $client = new Client();

        //开放自定义
        $request->before($client);

        //头信息设置
        $options = $request->options();
        $this->headers($request, $options);

        return $client->request($request->method(), $url, $options);
    }

    /**
     * 选择服务
     *
     * @param array $services 服务
     * @return array 服务节点信息
     */
    protected function selectService(array $services): array
    {
        shuffle($services);

        return array_pop($services);
    }

    /**
     * 使用AK&SK初始化账号Client
     *
     * @return Mse Client
     */
    public function createClient(array $properties): Mse
    {
        $config = new Config([
            "accessKeyId"     => $properties['access_key_id'],
            "accessKeySecret" => $properties['access_key_secret']
        ]);

        $config->endpoint = $properties['endpoint'];

        return new Mse($config);
    }

    /**
     * 验证参数
     *
     * @param array $properties 配置
     * @return void
     */
    public function checkParameter(array $properties)
    {
        if (empty($properties['access_key_id'])) {
            throw new \InvalidArgumentException("access_key_id cannot be empty");
        }

        if (empty($properties['access_key_secret'])) {
            throw new \InvalidArgumentException("access_key_secret cannot be empty");
        }

        if (empty($properties['endpoint'])) {
            throw new \InvalidArgumentException("endpoint cannot be empty");
        }
    }
}
