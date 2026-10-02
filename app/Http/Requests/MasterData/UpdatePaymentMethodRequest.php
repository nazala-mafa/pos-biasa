<?php

namespace App\Http\Requests\MasterData;

class UpdatePaymentMethodRequest extends StorePaymentMethodRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('payment-methods.update');
    }

    protected function recordId(): ?int
    {
        return (int) $this->route('payment_method');
    }
}
