<?php

namespace App\Support\Pagination;

use Illuminate\Http\Request;

class PaginationResolver
{
    public const DEFAULT = 20;

    public const MAXIMUM = 100;

    public function resolve(Request $request): int
    {
        $value = $request->input('per_page', self::DEFAULT);
        if (is_array($value) || filter_var($value, FILTER_VALIDATE_INT) === false) {
            return self::DEFAULT;
        }

        return min(self::MAXIMUM, max(1, (int) $value));
    }
}
