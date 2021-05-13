<?php
/**
 * Основные параметры WordPress.
 *
 * Скрипт для создания wp-config.php использует этот файл в процессе
 * установки. Необязательно использовать веб-интерфейс, можно
 * скопировать файл в "wp-config.php" и заполнить значения вручную.
 *
 * Этот файл содержит следующие параметры:
 *
 * * Настройки MySQL
 * * Секретные ключи
 * * Префикс таблиц базы данных
 * * ABSPATH
 *
 * @link https://ru.wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

require_once __DIR__ . '/../vendor/autoload.php';

// ** Параметры MySQL: Эту информацию можно получить у вашего хостинг-провайдера ** //
/** Имя базы данных для WordPress */
define( 'DB_NAME', getenv('MYSQL_DATABASE', true) ?: getenv('MYSQL_DATABASE') );

/** Имя пользователя MySQL */
define( 'DB_USER', getenv('MYSQL_USER', true) ?: getenv('MYSQL_USER') );

/** Пароль к базе данных MySQL */
define( 'DB_PASSWORD', getenv('MYSQL_PASSWORD', true) ?: getenv('MYSQL_PASSWORD') );

/** Имя сервера MySQL */
define( 'DB_HOST', getenv('MYSQL_HOST', true) ?: getenv('MYSQL_HOST') );

/** Кодировка базы данных для создания таблиц. */
define( 'DB_CHARSET', 'utf8mb4' );

/** Схема сопоставления. Не меняйте, если не уверены. */
define( 'DB_COLLATE', '' );

/**#@+
 * Уникальные ключи и соли для аутентификации.
 *
 * Смените значение каждой константы на уникальную фразу.
 * Можно сгенерировать их с помощью {@link https://api.wordpress.org/secret-key/1.1/salt/ сервиса ключей на WordPress.org}
 * Можно изменить их, чтобы сделать существующие файлы cookies недействительными. Пользователям потребуется авторизоваться снова.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '-s0NBW!E+E`201a0UD{A>{DF.]p[P6`Q8*0>y[|<rtq65d!:B)}NI.Zq0LJO4I|/' );
define( 'SECURE_AUTH_KEY',  '2(#TMo`p%F<HER%2N__R%irWpioDTO|6?|o{Y>Xv=W,TrH>Q[TWN=rPzZ9h1)E+G' );
define( 'LOGGED_IN_KEY',    '#p+^-bHOx]D?N[#<M7KxL1(Y(*0h0jZ>K4`E<H/2H^|9*NrnH7Scfp;?@q|$+`hX' );
define( 'NONCE_KEY',        ' U8gqxgr~Vu(N!rF-bp`qtY2 =^|kQTm$O*pZEo<LV2xz(?ziQ1(o%>FrR[T}fO_' );
define( 'AUTH_SALT',        'Cu;GtEx{]7o)!1yhwI6}D k]Omr92<&6@uM4#&^9S-1`!3wS8foR)XENMag3^UiJ' );
define( 'SECURE_AUTH_SALT', '4k:4y?qHuv_qzFQ<#L,fI2m*1RAEP$)8@ZM*TWO{MSB0)3q2N7.d1AV1{/q*0[5`' );
define( 'LOGGED_IN_SALT',   '4vw1!eff=FpZSm^bA! RtZu?8?l.-/OA&S)G+J@GSWDP4w>LEq(`M0%k6j8DCRAo' );
define( 'NONCE_SALT',       'VFqBkJckB`9M:w*T[DaeS|5Gu?Eo-uY)I]EMo0dU|a8#G{OZrpUf!2pw}Y`+Bdj ' );

/**#@-*/

/**
 * Префикс таблиц в базе данных WordPress.
 *
 * Можно установить несколько сайтов в одну базу данных, если использовать
 * разные префиксы. Пожалуйста, указывайте только цифры, буквы и знак подчеркивания.
 */
$table_prefix = 'wp_';

/**
 * Для разработчиков: Режим отладки WordPress.
 *
 * Измените это значение на true, чтобы включить отображение уведомлений при разработке.
 * Разработчикам плагинов и тем настоятельно рекомендуется использовать WP_DEBUG
 * в своём рабочем окружении.
 *
 * Информацию о других отладочных константах можно найти в документации.
 *
 * @link https://ru.wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* Это всё, дальше не редактируем. Успехов! */

/** Абсолютный путь к директории WordPress. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Инициализирует переменные WordPress и подключает файлы. */
require_once ABSPATH . 'wp-settings.php';
