<?php

namespace App\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class RequireApiPermission
{
    public function __construct(
        public readonly string $permission,
    ) {
    }
}
