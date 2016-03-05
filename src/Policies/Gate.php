<?php

namespace Mnabialek\LaravelAuthorize\Policies;

use Illuminate\Support\Str;

class Gate extends \Illuminate\Auth\Access\Gate
{
    /**
     * Resolve the callback for a policy check.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     * @param  string $ability
     * @param  array $arguments
     *
     * @return callable
     */
    protected function resolvePolicyCallback($user, $ability, array $arguments)
    {
        return function () use ($user, $ability, $arguments) {
            $instance = $this->getPolicyFor($arguments[0]);
            
            if (method_exists($instance, 'before')) {
                // We will prepend the user and ability onto the arguments so that the before
                // callback can determine which ability is being called. Then we will call
                // into the policy before methods with the arguments and get the result.
                $beforeArguments = array_merge([$user, $ability],
                    $this->getPolicyArguments($arguments));

                $result = call_user_func_array(
                    [$instance, 'before'], $beforeArguments
                );

                // If we received a non-null result from the before method, we will return it
                // as the result of a check. This allows developers to override the checks
                // in the policy and return a result for all rules defined in the class.
                if (!is_null($result)) {
                    return $result;
                }
            }

            if (strpos($ability, '-') !== false) {
                $ability = Str::camel($ability);
            }

            if (!is_callable([$instance, $ability])) {
                return false;
            }

            return call_user_func_array(
                [$instance, $ability], array_merge([$user],
                    $this->getPolicyArguments($arguments))
            );
        };
    }

    /**
     * Get policy arguments
     *
     * @param array $arguments
     *
     * @return array
     */
    protected function getPolicyArguments(array $arguments)
    {
        // if 1st argument is controller name we want to exclude it from
        // arguments we will apply to policy methods (we won't need it for
        // anything)
        if ($arguments && $this->isController($arguments[0])) {
            $arguments = array_splice($arguments, 1);
        }

        return $arguments;
    }

    /**
     * Verify whether given resource is controller
     *
     * @param string $resourceName
     *
     * @return bool
     */
    protected function isController($resourceName)
    {
        return (strpos($resourceName, 'Controller') !== false);
    }
}
