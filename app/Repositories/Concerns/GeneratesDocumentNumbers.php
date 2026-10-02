<?php

namespace App\Repositories\Concerns;

use Illuminate\Database\Eloquent\Relations\HasMany;

trait GeneratesDocumentNumbers
{
    /**
     * Document number in the form PREFIX-YYYYMMDD-0001, restarting every day within the outlet.
     * The records must be the outlet's own relation and have a "number" column.
     *
     * @param  HasMany<*, *>  $outletRecords
     */
    protected function nextDocumentNumber(HasMany $outletRecords, string $prefix): string
    {
        $prefix .= '-'.now()->format('Ymd').'-';
        $sequence = $outletRecords->where('number', 'like', "{$prefix}%")->count() + 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
