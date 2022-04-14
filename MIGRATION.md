# Migration

## v5.x to v6.x

The interface `RequestOptions` is replaced with the enaum `RequestOption`.

```php
$request->is_delete;
$request->is_safe;
```
```php
$request->method->is_delete();
$request->method->is_safe();
```
