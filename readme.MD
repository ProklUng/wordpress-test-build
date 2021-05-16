# Wordpress в виде минимальной сборки для нужд тестирования + инструменты

**INTERNAL**

## Установка

1) composer.json:

```json
  "repositories": [
    {
      "type": "git",
      "url": "https://github.com/proklung/wordpress-test-build"
    }
  ]
```

2) `composer require proklung/wordpress-test-build`

## Нюансы

На текущий момент внутри русская версия `5.7.2` Wordpress.

Базовый класс для тестов - `WordpressableTestCase`. Запускает, приложенный к пакету Wordpress и позволяет использовать в тестах
его API.

Параметры доступа к БД определяются в методе `setupDatabaseData` базового класса `WordpressableTestCase`.

Если база на момент запуска не существует, то будет создана.

По умолчанию:

```php
    protected function setupDatabaseData() : void
    {
        putenv('MYSQL_HOST=localhost');
        putenv('MYSQL_DATABASE=wordpress_ci');
        putenv('MYSQL_USER=root');
        putenv('MYSQL_PASSWORD=');
    }
```

### Управление

#### Трэйт ResetDatabaseTrait

Указание сбрасывать базу перед каждым тестом и загружать по новой.

#### Трэйт CustomDumpTrait

Сбрасывать базу и загружать кастомный дамп базы. 

Путь к дампу указывается в методе `getDumpPath` теста:

```php
    protected function getDumpPath() : string
    {
        return $_SERVER['DOCUMENT_ROOT'] . '/Tests/dump/dump.sql';
    }

```
Действует только в сочетании с `ResetDatabaseTrait`.

#### Трэйт UseMigrationsTrait

Указание запускать миграции перед каждым тестом. 

Под капотом урезанная версия [пакета](https://github.com/ProklUng/wp.migrations), так что подходят миграции и от него. 
Миграция наследуется не от класса `Arrilot\BitrixMigrations\BaseMigrations\WordpressMigration`.

Путь к директории с миграциями указывается в методе `getMigrationsDir` теста:

```php
    protected function getMigrationsDir() : string
    {
        return __DIR__ . '/../migrations';
    }
```

К трэйту приложен метод-хелпер `makeMigration` для создания миграций по шаблону.

```php
    protected function makeMigration(string $name, string $template) : void
```

Доступные шаблоны:

<table>
<tr><th>Название</th><th>Описание</th><th>Алиасы</th></tr>
<tr>
    <td>`default`</td>
    <td>Чистый шаблон по умолчанию</td>
    <td></td>
</tr>

<tr>
    <td>`query`</td>
    <td>Произвольный запрос в БД</td>
    <td></td>
</tr>
<tr>
    <td>`add_table`</td>
    <td>Создание таблицы через</td>
    <td>`create_table`</td>
</tr>
<tr>
    <td>`delete_table`</td>
    <td>Удаление таблицы</td>
    <td>`drop_table`</td>
</tr>
</table>

#### Трэйт ActivatePluginsTrait

Некоторые популярные плагины (WTF?!) не могут быть установлены с помощью композера. Приходится воротить всякое.

Трэйт с функционалом (метод `activatePlugins`) инсталляции (копирование из заданной папки) и активации
плагинов.

Определяются два свойства:

- `pluginSrcDir` - путь к директории, где лежат исходники плагинов (для копирования)
- `plugins` - массив вида 'директория с плагином' => путь к основному файлу плагина. Задается в конечном тесте:

```php

    protected static function getWordpressBaseDir() : string
    {
        return __DIR__. '/../../files';
    }

   protected static $pluginSrcDir = __DIR__ . '/files';

   // Скопирует ACF плагин из папки __DIR__ . '/files' в папку, где лежит Wordpress.
   protected function setUp(): void
    {
        parent::setUp();

        static::$plugins = [
            'advanced-custom-fields' => 'advanced-custom-fields/acf.php'
        ];
    }
```

#### Прочее

1) Статический метод `getWordpressBaseDir` - путь к месту, где лежит Wordpress.