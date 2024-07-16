<?php

namespace Xincheng\Launcher\Service;

use InvalidArgumentException;
use Xincheng\Launcher\Exception\ServiceNotFoundException;
use Xincheng\Launcher\Handler\HttpServiceHandler;
use Xincheng\Launcher\Handler\MesNacosServiceHandler;
use Xincheng\Launcher\Handler\NacosServiceHandler;
use Xincheng\Launcher\Handler\ServiceHandler;

/**
 * 服务管理器
 *
 * @author mragon
 * @since  2023-06-13 9:56
 */
class ServiceManager
{
    /**
     * @var array 服务集合
     */
    private array $services = [];

    /**
     * @var array 服务处理器
     */
    private array $handles = [];

    public function __construct()
    {
        $this->loadHandles();
    }

    /**
     * 加载服务
     *
     * @param array $services 服务
     * @return ServiceManager
     */
    public static function load(array $services): ServiceManager
    {
        $manager = new ServiceManager();

        if (empty($services)) {
            return $manager;
        }

        foreach ($services as $service) {
            self::verifyArgument($service);

            $manager->services[$service['name']] = ServiceMeta::build($service['name'], $service['type'], $service['target']);
        }

        return $manager;
    }

    /**
     * 加载服务处理器
     *
     * @return void
     */
    public function loadHandles(): void
    {
        $this->handles = [
            ServiceConstant::$NACOS_SERVICE     => new NacosServiceHandler(),
            ServiceConstant::$MES_NACOS_SERVICE => new MesNacosServiceHandler(),
            ServiceConstant::$HTTP_SERVICE      => new HttpServiceHandler(),
        ];
    }

    /**
     * 匹配服务
     *
     * @param string $server
     * @return ServiceHandler
     */
    public function match(string $server): ServiceHandler
    {
        if (empty($server) || !isset($this->services[$server])) {
            throw new ServiceNotFoundException("未找服务,请检查服务名称");
        }

        $meta = $this->services[$server];

        if (!isset($this->handles[$meta->getType()])) {
            throw new InvalidArgumentException($meta->getType() . "未知的服务类型");
        }

        return $this->handles[$meta->getType()];
    }

    /**
     * 验证服务参数
     *
     * @param array $service
     * @return void
     */
    public static function verifyArgument(array $service): void
    {
        if (!isset($service['name']) || !isset($service['type']) || !isset($service['target'])) {
            throw new InvalidArgumentException("服务必配置须包含服务name,type,target");
        }
    }
}