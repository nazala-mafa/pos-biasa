<?php

namespace App\Repositories;

use App\Models\CashierShift;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class CashierShiftRepository
{
    /**
     * @return Collection<int, CashierShift>
     */
    public function history(Team $outlet): Collection
    {
        return $outlet->cashierShifts()
            ->with('user:id,name')
            ->latest('opened_at')
            ->latest('id')
            ->get();
    }

    public function findOrFail(Team $outlet, int $id): CashierShift
    {
        return $outlet->cashierShifts()->with('user:id,name')->findOrFail($id);
    }

    /**
     * The shift the user is currently working, if any.
     */
    public function current(Team $outlet, User $user): ?CashierShift
    {
        return $outlet->cashierShifts()->where('user_id', $user->id)->whereNull('closed_at')->first();
    }

    /**
     * @throws ValidationException
     */
    public function open(Team $outlet, User $user, float $openingCash, ?string $note): CashierShift
    {
        if ($this->current($outlet, $user) !== null) {
            throw ValidationException::withMessages(['opening_cash' => __('You already have an open shift.')]);
        }

        return $outlet->cashierShifts()->create([
            'user_id' => $user->id,
            'opened_at' => now(),
            'opening_cash' => $openingCash,
            'note' => $note,
        ]);
    }

    /**
     * Close the shift, storing the cash the drawer should hold (opening cash plus cash sales)
     * next to the counted amount.
     *
     * @throws ValidationException
     */
    public function close(CashierShift $shift, float $closingCash, ?string $note): CashierShift
    {
        if ($shift->closed_at !== null) {
            throw ValidationException::withMessages(['closing_cash' => __('This shift is already closed.')]);
        }

        $shift->update([
            'closed_at' => now(),
            'expected_cash' => $this->expectedCash($shift),
            'closing_cash' => $closingCash,
            'note' => $note ?? $shift->note,
        ]);

        return $shift;
    }

    public function cashSales(CashierShift $shift): float
    {
        return (float) $shift->sales()
            ->whereHas('paymentMethod', fn ($query) => $query->where('is_cash', true))
            ->sum('total');
    }

    public function expectedCash(CashierShift $shift): float
    {
        return round((float) $shift->opening_cash + $this->cashSales($shift), 2);
    }
}
