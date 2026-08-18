<?php

namespace App\Helpers;

class MarketConditionHelper
{
    /**
     * Classify a market condition based on absorption rate and average days on market.
     *
     * Canonical thresholds (single source of truth):
     *   Strong Seller's Market : absorption > 25%  AND  avg DOM > 0 AND < 25 days
     *   Seller's Market        : absorption >= 20%
     *   Balanced Market        : absorption >= 12%
     *   Buyer's Market         : absorption < 12%
     *
     * @param  float $absorptionRate  Absorption rate as a percentage (e.g. 14.3 for 14.3%)
     * @param  int   $avgDom          Average days on market (0 = unknown)
     * @return array{label: string, color: string, class: string}
     */
    public static function classify(float $absorptionRate, int $avgDom): array
    {
        if ($absorptionRate > 25 && $avgDom > 0 && $avgDom < 25) {
            return [
                'label' => "Strong Seller's Market",
                'color' => '#c0392b',
                'class' => 'verdict-red',
            ];
        }

        if ($absorptionRate >= 20) {
            return [
                'label' => "Seller's Market",
                'color' => '#e67e22',
                'class' => 'verdict-orange',
            ];
        }

        if ($absorptionRate >= 12) {
            return [
                'label' => "Balanced Market",
                'color' => '#f39c12',
                'class' => 'verdict-yellow',
            ];
        }

        return [
            'label' => "Buyer's Market",
            'color' => '#2980b9',
            'class' => 'verdict-blue',
        ];
    }
}
