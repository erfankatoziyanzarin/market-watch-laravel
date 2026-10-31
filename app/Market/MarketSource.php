<?php

namespace App\Market;

interface MarketSource
{
    public function fetch(): MarketSnapshot;
}
