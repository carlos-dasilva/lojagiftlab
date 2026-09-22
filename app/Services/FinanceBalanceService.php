<?php

namespace App\Services;

use App\Models\CreditSale;
use App\Models\Payable;
use App\Models\Sale;
use App\Support\Money;

class FinanceBalanceService
{
    public function totals(): array
    {
        $balance = 0;
        $pending = 0;
        foreach (Sale::lazyById(500) as $sale) {
            $balance += $sale->net_total_cents;
        }
        foreach (CreditSale::with('items')->whereNotNull('received_at')->lazyById(500) as $credit) {
            $balance += $credit->net_total_cents;
        }
        foreach (Payable::lazyById(500) as $payable) {
            $amount = Money::cents($payable->amount);
            if ($payable->paid_at !== null) {
                $balance -= $amount;
            } else {
                $pending += $amount;
            }
        }

        return ['balance_cents' => $balance, 'pending_cents' => $pending];
    }
}
