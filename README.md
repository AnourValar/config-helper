# Laravel Config Helpers

## Installation

```bash
composer require anourvalar/config-helper
```


## Usage

### Config example
```php
// config/example.php

return [
    'user_role' => [
        'admin' => ['title' => 'Administrator', 'super_user' => true],
        'maintainer' => ['title' => 'Maintainer', 'super_user' => true],
        'moderator' => ['title' => 'Moderator'],
        'user' => ['title' => 'User', 'register_via_form' => true],
    ],
];
```


### Get filtered keys of config
```php
\ConfigHelper::keys('example.user_role', ['super_user' => true]); // ['admin', 'maintainer']
```


### Get singleton key
```php
\ConfigHelper::key('example.user_role', ['register_via_form' => true]); // 'user'
```


### HTML select
```php
echo '<select>' . \ConfigHelper::toSelect('example.user_role') . '</select>';
```
