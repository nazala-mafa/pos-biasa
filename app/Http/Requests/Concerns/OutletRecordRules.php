<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;

trait OutletRecordRules
{
    /**
     * The id of the record being updated, or null when creating.
     */
    protected function recordId(): ?int
    {
        return null;
    }

    protected function outletId(): int
    {
        return $this->user()->current_team_id;
    }

    /**
     * Unique rule on a column within the current outlet, ignoring the record being updated.
     */
    protected function uniqueInOutlet(string $table, string $column = 'name'): Unique
    {
        return Rule::unique($table, $column)
            ->where('team_id', $this->outletId())
            ->ignore($this->recordId());
    }

    /**
     * Exists rule on the "id" column limited to the current outlet.
     */
    protected function existsInOutlet(string $table): Exists
    {
        return Rule::exists($table, 'id')->where('team_id', $this->outletId());
    }
}
