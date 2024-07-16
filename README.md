### Launcher - 基于http协议的服务调用

目前支持 基于http协议的服务调用和基于nacos注册的服务调用，由于目前项目非常驻内存运行方式，所以无法将现有项目注册到nacos，仅实现通过名称自动路由nacos注册服务。可实现
php、java之前的互相调用以实现架构的灵活扩展

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
    public function run($request, array $params = [], array $options = [])
    {
        $request = Yii::createObject($request);

        $request->params = $params;
        $request->options = $options;

        return $this->launcher->run($request);
    }
}
```

### 缓存配置

通过实现CacheInterface接口实现Launcher缓存

```php
<?php

namespace common\launcher;

use Xincheng\Launcher\CacheInterface;
use Yii;

/**
 * LauncherCache
 *
 * @author: morgan
 * @slice : 2023-06-27 10:44:14
 */
class LauncherCache implements CacheInterface
{
    /**
     * 是否存在key
     *
     * @param string $key
     * @return bool
     */
    public function hasKey(string $key): bool
    {
        return Yii::$app->cache->get($key) !== false;
    }

    /**
     * 设置缓存
     *
     * @param string $key      key
     * @param mixed  $value    值
     * @param int    $duration 有效期(秒)
     * @return void
     */
    public function set(string $key, $value, int $duration)
    {
        Yii::$app->cache->set($key, $value, $duration);
    }

    /**
     * 获取缓存
     *
     * @param string $key
     * @return mixed
     */
    public function get(string $key)
    {
        return Yii::$app->cache->get($key);
    }

    /**
     * 删除缓存
     *
     * @param string $key key
     * @return void
     */
    public function del(string $key)
    {
        Yii::$app->cache->delete($key);
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


**console发起请求**

继承 ```ConsoleBaseRequest``` 即可，其他和Web一致

### 控制器基类鉴权控制

如 **BaseController**

因为后面的所有外部请求经统一网关，所以内部系统之前不必进行鉴权，http头信息中也携带了用户信息

```php
public function init(){
    //... code
    //仅在 x-platform 不存在或 x-platform 等于 web 时进行授权认证
    if (!isset($_SERVER['HTTP_X_PLATFORM']) || $_SERVER['HTTP_X_PLATFORM'] === Constants::PLATFORM_WEB) {
        // 此处的鉴权其实是非必须的，统一网关解析token后会在请求中添加 x-tenant-id、x-user-id、x-request-id、x-platform
        // 可直接通过请求头获取即可
        $login = XcAuth::login();
        $login->isLogin();
        define('UID', 2);
        define("UID_NAME", 'dev');
        defined('TENANT_ID') or define('TENANT_ID', $login->getTenantId());
        defined('USER_ID') or define('USER_ID', $login->getUserId());
    }
}
```

### 调用 

```php
$body = Yii::$app->launcher->run(NacosRequest::class);

echo $body->getBody();
```

## 
