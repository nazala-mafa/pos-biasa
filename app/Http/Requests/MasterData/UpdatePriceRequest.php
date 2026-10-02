<?php

namespace App\Http\Requests\MasterData;

class UpdatePriceRequest extends StorePriceRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('prices.update');
    }

    protected function recordId(): ?int
    {
        return (int) $this->route('price');
    }
}
