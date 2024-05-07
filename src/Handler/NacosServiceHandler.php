<?php

namespace Xincheng\Launcher\Handler;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use Xincheng\Launcher\Exception\ServiceNotFoundException;
use Xincheng\Launcher\Request\RequestContract;
use Xincheng\Launcher\Service\ServiceConstant;
use Psr\Http\Message\ResponseInterface;
use Xincheng\Launcher\Utils\ArrUtils;

/**
 * Nacos服务处理器
 *
 * @author morgan
 * @since  2023-06-13 14:19
 */
class NacosServiceHandler extends BaseServiceHandler
{
    use HandlerHelper;

    /**
     * @var string nacos api 服务列表
     */
    private static string $API_SERVICE_LIST = '/nacos/v1/ns/instance/list?';

    /**
     * @var string 鉴权
     */
    private static string $API_AUTH_LOGIN = '/nacos/v1/auth/login';

    /**
     * 执行Nacos服务调用
     *
     * @return object 执行结果
     * @throws GuzzleException 请求异常
     */
    public function handle(RequestContract $request, array $properties)
    {
        $auth = $this->authorize($properties);

        $service = $this->getService($request->server(), $properties);
        $target = $service['target'][0] ?? "";

        //获取服务实例列表
        $services = $this->serviceInstants($target, $properties, $auth);

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
     * @param string $name       服务名称
     * @param array  $properties 配置信息
     * @param object $auth       授权
     * @return array 节点信息
     */
    protected function serviceInstants(string $name, array $properties, object $auth): array
    {
        $config = $properties['nacos'];

        if (empty($config)) {
            throw new InvalidArgumentException("请检查配置是否包含nacos配置");
        }

        $client = new Client();
        $response = $client->get($config['host'] . self::$API_SERVICE_LIST . $this->queryServiceParameters($name, $config, $auth));
        $result = json_decode((string)$response->getBody());

        return $result->hosts;
    }

    /**
     * 构建查村服务参数
     *
     * @param string $name
     * @param array  $config 配置
     * @param object $auth   授权信息
     * @return string 查询服务参数
     */
    protected function queryServiceParameters(string $name, array $config, object $auth): string
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

        $query['accessToken'] = $auth->accessToken;

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
     * @param object          $node    服务节点
     * @return ResponseInterface 相应信息
     * @throws GuzzleException 请求异常
     */
    protected function execute(RequestContract $request, object $node): ResponseInterface
    {
        $options = [];
        $url = $node->ip . ':' . $node->port . $request->router();
        $client = new Client();

        if (!empty($request->params())) {
            $url .= "?" . $request->buildQuery();
        }

        //开放自定义
        $request->before($client);

        //头信息设置
        $this->headers($request, $options);

        return $client->request($request->method(), $url, ArrUtils::merge($request->options(), $options));
    }

    /**
     * nacos 鉴权
     *
     * @param array $properties
     * @return object
     */
    protected function authorize(array $properties): object
    {
        $config = $properties['nacos'];
        $tokenKey = $this->tokenKey($config);

        if ($this->cache !== null) {
            if ($this->cache->hasKey($tokenKey)) {
                return $this->cache->get($tokenKey);
            } else {
                $body = $this->authorizeRequest($config);

                $this->cache->set($tokenKey, $body, $body->tokenTtl);

                return $body;
            }
        } else {
            return $this->authorizeRequest($config);
        }
    }

    /**
     * 鉴权请求
     *
     * @param array $config 配置
     * @return object|mixed
     */
    protected function authorizeRequest(array $config): object
    {
        $client = new Client();

        $body = $client->post($config['host'] . self::$API_AUTH_LOGIN, [
            'form_params' => [
                'username' => $config['username'],
                'password' => $config['password'],
            ]
        ]);

        return json_decode($body->getBody());
    }

    /**
     * token key
     *
     * @param array $config 配置
     * @return string token key
     */
    protected function tokenKey(array $config): string
    {
        return sprintf("nacos:token:%s", $config['host']);
    }
}