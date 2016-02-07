<?php

namespace Mnabialek\LaravelAuthorize\Contracts;

interface PermissionHandler
{
    /**
     * Get list of all permissions that resource has
     *
     * @param Roleable $resource
     *
     * @return array
     */
    public function getPermissions(Roleable $resource);
}
