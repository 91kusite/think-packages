<?php
namespace kusite\package\libs\middleware;

class RegisterPackage
{
    public function handle($request, \Closure $next)
    {
        // 注册当前路由为组件路由
        $request->isPackage = true;

        return $next($request);
    }
}
