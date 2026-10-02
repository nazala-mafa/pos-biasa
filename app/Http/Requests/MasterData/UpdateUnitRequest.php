<?php

namespace App\Http\Requests\MasterData;

class UpdateUnitRequest extends StoreUnitRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('units.update');
    }

    protected function recordId(): ?int
    {
        return (int) $this->route('unit');
    }
}
