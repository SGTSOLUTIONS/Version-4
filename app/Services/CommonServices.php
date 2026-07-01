<?php

namespace App\Services;

class TaxService
{
    public function calculateTax($amount)
    {
        return $amount * 0.18;
    }
}
