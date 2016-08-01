<?php
namespace Concrete\Core\Attribute\Key;

/*
 * Factory class for creating instances of the Attribute key category entity.
 */
use Concrete\Core\Support\Facade\Facade;

class Category extends Facade
{

    /**
     * @Deprecated
     */
    const ASET_ALLOW_NONE = 0;

    /**
     * @Deprecated
     */
    const ASET_ALLOW_SINGLE = 1;

    /**
     * @Deprecated
     */
    const ASET_ALLOW_MULTIPLE = 2;

    public static function getFacadeAccessor()
    {
        return 'Concrete\Core\Attribute\Category\CategoryService';
    }
}
