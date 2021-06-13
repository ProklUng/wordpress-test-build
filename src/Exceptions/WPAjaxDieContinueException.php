<?php

namespace Prokl\WordpressCi\Exceptions;

/**
 * Class WPAjaxDieContinueException
 * @package Prokl\WordpressCi\Exceptions
 *
 * Exception for cases of wp_die(), for Ajax tests.
 *
 * This means the execution of the Ajax function should be halted, but the unit test
 * can continue. The function finished normally and there was no error (output happened,
 * but wp_die was called to end execution). This is used with WP_Ajax_Response::send().
 */
class WPAjaxDieContinueException extends WPDieException
{

}