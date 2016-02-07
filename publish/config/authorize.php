<?php

return [
    /**
     * Super roles - in role mode, those roles will be automatically added to 
     * other roles, and in permission mode for those roles won't
     * be made any detailed checks - in case if user is assigned to any of those
     * roles it will be assumed he has permission. If you leave it empty, no
     * super roles will be used
     */
    'super_roles' => [
        'admin',
    ],

    /**
     * Available permissions for roles (those are by default used by
     * PermissionConfigHandler unless you create custom handler that will read
     * them for example from database)
     */
    'permissions' => [

        /**
         * List of all available permissions in system. You should keep it
         * up-to-date. Format of those permissions is:
         * name of group in your Policy class . controller method name
         */
        'all' => [
            'sample.store', // you can remove this - this is only sample permission
        ],

        /**
         * Assignment of above permissions to user roles
         * If for any role you set only '*' as permission it means all
         * permissions will be available for this role
         */
        'roles' => [
            'admin' => [
                '*', // all permissions (don't add anything into this array)
            ],

            'user' => [
                'sample.store', // you can remove this - this is only sample permission
            ],
        ],
    ],

    /**
     * Module bindings (don't touch them unless you want write custom permission handling)
     */
    'bindings' => [
        \Mnabialek\LaravelAuthorize\Contracts\Permissionable::class => \Mnabialek\LaravelAuthorize\Services\Permission::class,
        \Mnabialek\LaravelAuthorize\Contracts\PermissionHandler::class => \Mnabialek\LaravelAuthorize\Services\ConfigPermissionHandler::class,
    ],
];
