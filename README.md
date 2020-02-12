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
\ConfigHelper::getKeys('example.user_role', ['super_user' => true]); // ['admin', 'maintainer']
```

### Get singleton key
```php
\ConfigHelper::getKey('example.user_role', ['register_via_form' => true]); // 'user'
```
