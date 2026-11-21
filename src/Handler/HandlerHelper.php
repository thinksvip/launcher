<?php

namespace Xincheng\Launcher\Handler;

use GuzzleHttp\Psr7\Uri;
use Xincheng\Launcher\Constants;
use Xincheng\Launcher\Request\RequestContract;
use Xincheng\Launcher\Utils\GenerateUtils;

/**
 * HandlerHelper
 *
 * @author: morgan
 * @slice : 2023-06-26 17:35:36
 */
trait HandlerHelper
{
    protected function requestUrl(string $target, RequestContract $request): string
    {
        $url = $target . $request->router();
        if (empty($request->params())) {
            return $url;
        }

        $uri = new Uri($url);
        $query = $uri->getQuery();
        return (string) $uri->withQuery(($query === '' ? '' : $query . '&') . $request->buildQuery());
    }

    /**
     * 请头信息设置
     *
     * @param RequestContract $request 请求
     * @param array           $options 配置信息
     * @return void
     */
    protected function headers(RequestContract $request, array &$options)
    {
        $explicitHeaders = array_change_key_case($options['headers'] ?? []);
        $forwardedHeaders = [
            Constants::HTTP_HEADER_AUTHORIZATION => Constants::SERVER_HTTP_AUTHORIZATION,
            Constants::HTTP_X_TENANT_ID => Constants::SERVER_X_TENANT_ID,
            Constants::HTTP_X_USER_ID => Constants::SERVER_X_USER_ID,
        ];
        foreach ($forwardedHeaders as $header => $serverKey) {
            $value = $_SERVER[$serverKey] ?? '';
            if (!array_key_exists(strtolower($header), $explicitHeaders) && $value !== '') {
                $options['headers'][$header] = $value;
            }
        }

        $requestId = $_SERVER[Constants::SERVER_X_REQUEST_ID] ?? '';
        $platform = $_SERVER[Constants::SERVER_X_PLATFORM] ?? '';
        $contextHeaders = [
            Constants::HTTP_X_REQUEST_ID => $requestId !== '' ? $requestId : GenerateUtils::uuid(),
            Constants::HTTP_X_PLATFORM => $platform !== '' ? $platform : $request->platform(),
        ];
        // HTTP 头名不区分大小写，每个请求只应携带一份上下文。
        $options['headers'] = array_diff_ukey($options['headers'] ?? [], $contextHeaders, 'strcasecmp') + $contextHeaders;
    }
}
