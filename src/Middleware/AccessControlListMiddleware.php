<?php

namespace Tochka\JsonRpc\Middleware;

use Tochka\JsonRpc\Exceptions\JsonRpcException;
use Tochka\JsonRpc\Support\JsonRpcRequest;

class AccessControlListMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param JsonRpcRequest $request
     * @param callable       $next
     * @param array          $acl
     *
     * @return mixed
     * @throws \Tochka\JsonRpc\Exceptions\JsonRpcException
     */
    public function handle(JsonRpcRequest $request, $next, array $acl = [])
    {
        if (empty($request->controller) || empty($request->method)) {
            throw new JsonRpcException(JsonRpcException::CODE_INTERNAL_ERROR,
                'JsonRpc server configuration error: Place AccessControlListMiddleware after MethodClosureMiddleware in middleware list!');
        }

        $controllerName = get_class($request->controller);
        $methodName = $request->method;

        $service = $request->service;

        $globalRules = (array) ($acl['*'] ?? []);
        $controllerRules = (array) ($acl[$controllerName] ?? []);
        $methodRules = (array) ($acl[$controllerName . '@' . $methodName] ?? []);

        // если не попали ни под одно правило - значит сервису нельзя
        if (empty($globalRules) && empty($controllerRules) && empty($methodRules)) {
            throw new JsonRpcException(JsonRpcException::CODE_FORBIDDEN);
        }

        $this->checkRules($service, $methodRules);
        $this->checkRules($service, $controllerRules);
        $this->checkRules($service, $globalRules);

        return $next($request);
    }

    /**
     * @param string $service
     * @param array  $rules
     *
     * @throws JsonRpcException
     */
    protected function checkRules(string $service, array $rules): void
    {
        if (
            !empty($rules)
            && !in_array('*', $rules, true)
            && !in_array($service, $rules, true)
        ) {
            throw new JsonRpcException(JsonRpcException::CODE_FORBIDDEN);
        }
    }
}
