<?php

namespace Xincheng\Launcher\Service;

/**
 * 服务元数据
 *
 * @author mragon
 * @since 2023-06-13 9:56
 */
class ServiceMeta implements ServiceMetaContract
{

    /**
     * @var string 服务名称
     */
    private string $name;

    /**
     * @var string 路由类型
     */
    private string $type;

    /**
     * @var array 目标路径
     */
    private array $target;


    public function __construct(string $name, string $type, array $target)
    {
        $this->name = $name;
        $this->type = $type;
        $this->target = $target;
    }

    /**
     * 获取服务名称
     *
     * @return string 服务名称
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * 设置服务名称
     *
     * @param string $name 服务名称
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * 获取路由类型
     *
     * @return string 路由类型
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * 设置路由类型
     *
     * @param string $type
     */
    public function setType(string $type)
    {
        $this->type = $type;
    }

    /**
     * 获取目标路径
     *
     * @return array 目标路径
     */
    public function getTarget(): array
    {
        return $this->target;
    }

    /**
     * 设置目标路径
     *
     * @param array $target 目标路径
     */
    public function setTarget(array $target)
    {
        $this->target = $target;
    }

    /**
     * 构建服务元数据
     *
     * @param string $name 服务名称
     * @param string $type 服务类型
     * @param array $target 目标路径
     * @return ServiceMeta
     */
    public static function build(string $name, string $type, array $target): ServiceMeta
    {
        return new ServiceMeta($name, $type, $target);
    }
}