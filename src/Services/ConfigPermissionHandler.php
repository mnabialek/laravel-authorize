<?php

namespace Mnabialek\LaravelAuthorize\Services;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Logging\Log;
use Mnabialek\LaravelAuthorize\Contracts\PermissionHandler;
use Mnabialek\LaravelAuthorize\Contracts\Roleable;

class ConfigPermissionHandler implements PermissionHandler
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Log
     */
    protected $log;

    /**
     * Permissions config
     *
     * @var array
     */
    protected $permissions = [];

    /**
     * PermissionConfigHandler constructor.
     *
     * @param Config $config
     * @param Log $log
     */
    public function __construct(Config $config, Log $log)
    {
        $this->config = $config;
        $this->log = $log;
        $this->permissions = $this->config->get('authorize.permissions');
    }

    /**
     * Get list of all permissions that user has
     *
     * @param Roleable $resource
     *
     * @return array
     */
    public function getPermissions(Roleable $resource)
    {
        $userRoles = (array)$resource->getRoles();

        $permissions = [];
        foreach ($userRoles as $userRole) {
            $permissions = array_merge($permissions,
                $this->getPermissionsForRole($userRole));
        }

        return array_values(array_unique($permissions));
    }

    /**
     * Get permissions for given role
     *
     * @param string $role
     *
     * @return array
     * @throws \Exception
     */
    protected function getPermissionsForRole($role)
    {
        // we expect role is a string - if not, we need to throw exception
        if (!is_string($role)) {
            throw new \InvalidArgumentException('Role has to be a string');
        }
        
        if (!isset($this->permissions['roles'][$role])) {
            $this->log->critical("There are no roles assigned to role {$role}");

            return [];
        }

        $permissions = $this->permissions['roles'][$role];

        // for some roles (probably admin) we allow to specify only asterisk as
        // permission and in this case we will use all available permissions
        if (count($permissions) == 1 && $permissions[0] == '*') {
            return $this->permissions['all'];
        }

        return $permissions;
    }
}
