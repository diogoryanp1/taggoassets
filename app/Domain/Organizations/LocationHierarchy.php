<?php

namespace App\Domain\Organizations;

use App\Domain\Organizations\Models\Location;

final class LocationHierarchy
{
    public function acceptsParent(Location $location, ?Location $parent, int $unitId): bool
    {
        if ($parent === null) {
            return true;
        }

        if ($parent->id === $location->id || $parent->organizational_unit_id !== $unitId) {
            return false;
        }

        while ($parent->parent_id !== null) {
            if ($parent->parent_id === $location->id) {
                return false;
            }

            $parent = $parent->parent()->first();
            if ($parent === null) {
                return false;
            }
        }

        return true;
    }
}
