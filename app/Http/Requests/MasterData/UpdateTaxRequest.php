<?php

namespace App\Http\Requests\MasterData;

class UpdateTaxRequest extends StoreTaxRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('taxes.update');
    }

    protected function recordId(): ?int
    {
        return (int) $this->route('tax');
    }
}
