<?php

/*
 * This file is part of Vivo Group's Content Management System.
 * For the full copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 */

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return static function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
