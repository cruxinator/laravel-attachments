<?php

namespace VendorName\Skeleton\Facades;

use Illuminate\Support\Facades\Facade;
use VendorName\Skeleton\Facades\Contracts\SkeletonContract;

/**
 * @see \VendorName\Skeleton\Skeleton
 */
class Skeleton extends Facade
{
    protected static function getFacadeAccessor()
    {
        return SkeletonContract::class;
    }
}
