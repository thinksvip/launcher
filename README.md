### Launcher - 基于http协议的服务调用

目前支持 基于http协议的服务调用和基于nacos注册的服务调用，由于目前项目非常驻内存运行方式，所以无法将现有项目注册到nacos，仅实现通过名称自动路由nacos注册服务。可实现 php、java之前的互相调用以实现架构的灵活扩展

### 安装

```php
composer require xincheng/launcher
```

### 基础配置

项目编写时作者考虑到工程框架的多样性，故没有直接引入Yii Competent方式作为Yii扩展，而是以composer package方式组织，所以需要在项目进行初始化

配置如下：

```php
<?php

return [
    # 设置代理类，用于初始化launcher
    'class' => common\launcher\LauncherDelegate::class,
    # 配置信息
    'properties' => [
         # 服务列表
        'services' => [
            # http 服务示例
            [
                # 服务名称
                'name' => 'xxx', 
                # 目标服务器，可配置多个地址，目前根据随机策略方式进行访问
                'target' => ['http://xxx.xxx.xxx.xxx'],
                # 类型 目前仅支持 http nacos两种类型
                'type' => 'http'
            ],
            [
                'name' => 'xxx',
                # nacos 由于自动根据配置路由所以无需填写target，默认空数组即可
                'target' => [],
                'type' => 'nacos'
            ]
        ],
        # nacos配置信息
        'nacos' => [
            # nacos host设置
            'host' => 'xxx.xxx.xxx.xxx:8848',
            # nacos 用户名
            'username' => 'nacos',
            # nacos 密码
            'password' => 'xxxx',
            # nacos group
            'groupName' => 'DEFAULT_GROUP',
            #nacos namespace
            'namespaceId' => 'xxxx',
        ]
    ],
];


```

### 在Yii中初始化 仅做参考

```php
<?php

namespace common\launcher;

use Yii;
use yii\base\Component;
use Xincheng\Launcher\Launcher;
use yii\base\InvalidConfigException;

/**
 * LauncherDelegate
 *
 * @author: morgan
 * @slice : 2023-06-20 15:56:27
 */
class LauncherDelegate extends Component
{
    /**
     * @var array 配置属性
     */
    public array $properties = [];

    /**
     * @var Launcher 服务调用实例
     */
    private Launcher $launcher;

    /**
     * 初始化
     *
     * @return void
     */
    public function init()
    {
        $this->launcher = new Launcher($this->properties);
        $this->launcher->setCache(new LauncherCache());
    }

    /**
     * 执行调用
     *
     * @throws InvalidConfigException
     */
    public function run($request)
    {
        $request = Yii::createObject($request);

        return $this->launcher->run($request);
    }
}
```

### 编写第一个请求

请求分为web请求和console请求

**web请求**

只需要继承```WebBaseRequest```实现接口方法即可

- server 配置中的服务名称
- router 路由，目标服务的路由从host后面开始 如 https://www.baidu.com/hello/world 这里填写 /hello/world即可
- method 请求类型 http所有请求类型都支持,项目使用guzzle作为请求客户端
- options 可以自定义头信息、post请求body...具体参数查看guzzle文档
- before 可在请求发送前用户自定义设置，可以理解为hook

```php
<?php

namespace common\launcher\request;

use Xincheng\Launcher\Request\WebBaseRequest;

/**
 * DirectRequest
 *
 * @author: morgan
 * @slice : 2023-06-27 09:42:40
 */
class DirectRequest extends WebBaseRequest
{
    public function server(): string
    {
        return 'xc_goods';
    }

    public function router(): string
    {
        return '/test.php';
    }

    public function method(): string
    {
        return 'GET';
    }
}
```

### 调用

```php
$body = Yii::$app->launcher->run(NacosRequest::class);

echo $body->getBody();
```

