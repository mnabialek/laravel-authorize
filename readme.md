Laravel Authorize
===

This module makes managing user access to different parts of application easier. You can protect your routes with `authorize` middleware based on **user roles or user permissions** without adding any extra code to your controller to keep them as clean as no authorization were used at all.

## Installation

1. Run
   ```php   
   composer require mnabialek/laravel-authorize
   ```     
   in console to install this module
   
2. Open `config/app.php` and: 
  * Comment line with
        ```php
        Illuminate\Auth\AuthServiceProvider::class,
        ```
   * Add
    
       ```php
        Mnabialek\LaravelAuthorize\Providers\Auth::class,
        Mnabialek\LaravelAuthorize\Providers\Authorize::class,
       ```       
        in same section (`providers`)
3. Run

    ```php
    php artisan vendor:publish --provider="Mnabialek\LaravelAuthorize\Providers\Authorize"
    ```
    
    in your console to publish default configuration files, middleware, base policy class and unauthorized view
    
    
4. In `app/Http/Kernel.php` in `$routeMiddleware` add:
    
    ```php
    'authorize' => \App\Http\Middleware\Authorize::class,
    ```    
    
    to register `Authorize` middleware
    
5. Open `App\Http\Middleware\Authorize.php` and adjust `errorResponse` and `reportUnauthorizedAttempt` to your needs. In case defaults are fine to you, open `resources/views/errors/401.blade.php` and adjust this template to your needs - by default this view will be used if user has no permissions to given route.

6. Open your `User.php` model file and add

    ```php
    use Mnabialek\LaravelAuthorize\Contracts\Roleable as RoleableContract;`
    ```
    
    before class definition and make `User` class implement this interface, so it should look like this 
    
    ```php
    implements ..., RoleableContract
    ```
    
    As `...` you should leave all default interfaces this class `User` implements.
    
7. Make sure your `User` class implements `Roleable` Contract. In order to do that, you need to implement 2 methods: `hasRole` and `getRoles`.  To simplify this, you can use default `Roleable` Trait. Just put inside your `User` class:
   
    ```php
    use \Mnabialek\LaravelAuthorize\Traits\Roleable;
    ```
    
    Be aware this trait assumes you have `role` property for `User` model (what is equal that you have `role` column in your `users` table in database that hold your role name). In many cases it won't be true, so you need to override at least `getRoles` method to get valid user roles. Assuming you have one to many `role` relationship (user is only assigned to single role), custom implementation could look like this:
    
    ```php
    public function getRoles()
    {
        return $this->role ? [$this->role->slug]: [];
    }
    ```
    
    Of course, if user can be assigned in your system to multiple roles or your database structure looks different, you should adjust this method code to match your application logic.
    

## Getting started

This module allows you to protect your routes with `authorize` middleware. You have 2 ways to use this middleware (you can use both in same application) - either based on `roles` or based on `permissions`

### 1. Role based authorization 

You can specify middleware with arguments for example `authorize:manager,employee` - in this case only user role will be verified. In this example if user has any role `manager` or `employee` they will be allowed to access route, otherwise they won't be allowed to do that. However in above example also users with `super_roles` will be allowed to do this (`super_roles` in `config/authorize.php`). So if you define in `super_roles` also `admin`, also users with `admin` role will be allowed to access this route so you don't need to specify `admin` role in case you specify other roles (but of course you can do this if you want). 

Nothing more needs to be configured to use this mode.

### 2. Permission based authorization

**In this option, you cannot use closures in routes protected by `authorize` middleware. Make sure you don't use them in those routes or you'll get exception when applying `authorize` middleware**

If you use middleware without any arguments for example `authorize`, it will take advantage of [Laravel authorization](https://laravel.com/docs/5.2/authorization) with some extra changes to this mechanism. By default Laravel suggests creating policies for Models but it might be more reasonable in some cases to use policies for controllers and that's what this module does.

#### Configuration

Open `config/authorize.php` and in `super_roles` put roles name for which you allow everything so no extra checks will be made. In most cases it's reasonable to put here `admin` role but in some cases you might want to leave this empty if you want to run mode detailed rules. Put all roles you use in your application into `roles` section of `permissions` section.
 
 
#### Protecting your controllers
  
Let's assume we have controller `UserController` with default REST actions - `index`, `show`, `create`, `store`, `edit`, `update`, `destroy` and we would like to to protect this controller with `authorize` middleware because we don't want all users to allow all actions from this controller.
   
First, we need to open `app\Providers\AuthServiceProvider.php` and add policy mapping for our controller in `$policies` property:
   
```php
\App\Http\Controllers\UserController::class => \App\Policies\UserControllerPolicy::class,
```
    
Now, let's create Policy class in `app/Policies/UserControllerPolicy.php` file with the following definition    
   
```php   
<?php

namespace App\Policies;

class UserControllerPolicy extends BasePolicyController
{
  protected $group = 'user';
}
```
  
Now, you need to open `config/authorize.php` and in section `available` in `permissions` you will add permissions you need to use in order to protect each controller method. By default permissions are in format `$group . controller method`. We defined in `UserControllerPolicy` group as `user` and in our controller we have the following methods:  `index`, `show`, `create`, `store`, `edit`, `update`, `destroy`, so our permissions should by default look like this:
    
```php
'user.index',
'user.show',
'user.create',
'user.store',
'user.edit',
'user.update',
'user.destroy',
```
     
But in fact in most cases you don't need permission for `create` at all. User should be able to run `create` method of controller only if they have permission to run `store` method. Same would apply to `edit` - they should be able to run `edit` only if they have permission to run `update` (this behaviour can be modified - see `Customization`), so let's add into those section only below permissions:
     
```php
'user.index',
'user.show',
'user.store',
'user.update',
'user.destroy',
```
     
Now, it's time to set those permissions for different roles. In section `roles` in `permissions` you have some example roles. You should put here roles that match your system roles names and assign to them any of those permissions. For `admin` user usually you want to allow everything, so you can add only `*` as permission and it means, that role `admin` will have all permissions defined in `available` section.         

Now make sure, you apply `authorize` middleware to `UserController` in your routes.php for example this way:

```php
Route::group(['middleware' => ['authorize']],
  function () {        
      Route::resource('users', 'UserController');
  });
```
and that's it! You've protected your first controller with `authorize` middleware.
       
If you want to protect another controller, just repeat those steps. You need to of course make sure you set `$group` property in your new policy class to unique value.
 
#### Advanced usage
 
By default as previously showed, you can create very simple policy class for your controller and that's it. However in real application, you might want to use more complex rules in order to verify if user has or not access to given controller method. For example user might have permission to update user, but they are allowed to update only his own account. 
  
Default flow for authorization verification looks like this:

- if user has super role (you can configure them in `super_roles` section in `config/authorize.php`) it will have permission for anything and no custom methods will be run
- if user does not have necessary permission, no further checks will be made
- if user has necessary permission, we verify if there are custom method in Policy for ability (the method name should match the method name from controller). If there's not, user will be allowed to run this action
- however if there is custom method in Policy for ability, whether user can run this action or not will depend on result of custom method for this ability.

Let's assume we have route like this:

```php
Route::show('/users/{users}/{type}', 'UserController@show')
```
    
and we would like to allow displaying all users only for admin role, and for others we would like to to allow displaying only their own account.
    
we could have registered in `RouteServiceProvider.php` the following route model binding:
     
```php
$router->model('users', 'App\User');
```
    
So, now in our `UserControllerPolicy` class we could create the following method:
    
```php
public function show($user, $displayedUser, $type)
{
  if ($displayedUser->id == $user->id) {
      return true;
  }
  return false;
}
```
    
And now this extra method will be used after verification if user has `user.show` permission. Because in above case we assume we have admin role in `super_roles` that's it what we need to use here.
    
Of course, in addition, you could use here also `$type` parameter or also request parameters (in case they should affect authorization) using `getRequest()` method or using property `$request` directly.    
 
## Customization

By default, all `create` and `edit` abilities will be automatically replaced by `store` and `update` because in most cases this will be desired behaviour. However, if you don't want to use it this way or you want to create custom ability mappings, just open `app/Policies/BasePolicyController` class and create custom `getAbilityMappings` method. Of course you could do it also in single Policy class for example `UserControllerPolicy` if you want to.

Also in some cases it could happen that for methods in 2 different controller you would like to use same permission. Then you could in one of your Policy classes, create custom `getPermissionMappings` method, for example:

```php
protected function getPermissionMappings()
{
  return ['store' => 'somethingelse.store];
}
```
    
and this way you could use permission not base on `group` assigned to current Policy class.

### Advanced customization

This module uses default implementation for the following interfaces:
 
- `Mnabialek\LaravelAuthorize\Contracts\Permissionable`
- `Mnabialek\LaravelAuthorize\Contracts\PermissionHandler`

But in some cases you might want to override default implementation or create custom one. For example you might want to use custom database handler to store permissions in database.
 
So if you want create custom implementation, just implement any of those 2 interfaces and in `config/authorize.php` in `bindings` section set your custom bindings for those interfaces. 

### Licence

This package is licenced under the [MIT license](http://opensource.org/licenses/MIT)
