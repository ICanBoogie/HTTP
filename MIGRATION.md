# Migration

## v5.x to v6.x

The interface `RequestOptions` is replaced with the enum `RequestOption`.

```php
$request->is_delete;
$request->is_safe;
```
```php
$request->method->is_delete();
$request->method->is_safe();
```

`Context` no longer implements `ArrayAccess` and no longer extends `Prototype`.

```php
$context['request']
$context->request;
```regexp
$context->get(Request::class);
```
