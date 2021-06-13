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

Класс `WordpressableAjaxTestCase` - особый случай для тестирования нативных ajax-обработчиков. По аналогии
c [WP_Ajax_UnitTestCase](https://infinum.com/handbook/books/wordpress/automated-testing-in-wordpress/basic-tests-using-phpunit/integration-testing#testing-ajax-callbacks).

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
        // Важно задать параметры перед вызовом родителя.
        static::$pluginSrcDir =  __DIR__ . '/files';
        static::$plugins = [
            'advanced-custom-fields' => 'advanced-custom-fields/acf.php'
        ];

        parent::setUp();
    }
```

#### Прочее

1) Статический метод `getWordpressBaseDir` - путь к месту, где лежит Wordpress.

2) Провайдеры данных для Faker:

##### Wordpress

- `permalink` - путь к посту по ID
- `fileContent` - контент файла
- `uploadDir` - путь к аплоаду
- `postId` - случайный ID поста
- `attachmentId` - случайная картинка
- `userId` - случайный ID пользователя
- `termId` - случайная таксономия (категория, тэг)

##### Picsum

- `picsum` - Картинка из picsum. Возвращает путь к загруженной картинке в рамках Wordpress.


#### Простой генератор тестового контента

Использование в миграциях

```php
    use Prokl\WordpressCi\FixtureGenerator\Repository\AttachmentRepository;
    use Prokl\WordpressCi\FixtureGenerator\Repository\PostRepository;
    use Prokl\WordpressCi\FixtureGenerator\Repository\TermRepository;
    use Prokl\WordpressCi\FixtureGenerator\Repository\UserRepository;

   public function up()
    {
        $faker = Helper::getFaker();

        AttachmentRepository::create(10); // 10 картинок
        TermRepository::create(10, 'category'); // 10 категорий
        TermRepository::create(5, 'post_tag'); // 10 тэгов
        UserRepository::create(5); // 5 пользователей

        // АСF поля. То, что отдает плагин ACF.
        acf_add_local_field_group(array(
            'key' => 'group_5e12e975546ec',
            'title' => 'Видео',
            'fields' => array(
                array(
                    'key' => 'field_5e130144fa51a',
                    'label' => 'Видео элемент',
                    'name' => 'video_element',
                    'type' => 'relationship',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => array(
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ),
                    'post_type' => array(
                        0 => 'myvideo',
                    ),
                    'taxonomy' => '',
                    'filters' => array(
                        0 => 'search',
                        1 => 'post_type',
                        2 => 'taxonomy',
                    ),
                    'elements' => array(
                        0 => 'featured_image',
                    ),
                    'min' => '',
                    'max' => '',
                    'return_format' => 'object',
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'post',
                    ),
                ),
            ),
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'hide_on_screen' => array(
                0 => 'discussion',
            ),
            'active' => true,
            'description' => '',
        ));

        // 10 постов с картинками и acf полями.
        PostRepository::create(10, [
            'acf' => [
                'video_element' => $faker->postId()
            ]
        ]);
    }
```
