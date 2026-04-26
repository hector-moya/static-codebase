<?php

/*
 * This file is part of Vivo Group's Content Management System.
 * For the full copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    #[\Override]
    public function boot(): void
    {
        date_default_timezone_set('Australia/Brisbane');

        parent::boot();
    }
}
