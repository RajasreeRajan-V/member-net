<?php

namespace App\Helpers;

use Carbon\Carbon;

class DateFormatter
{
    /**
     * Format any date/datetime to dd-mm-yyyy for API output.
     * Never returns null — returns '' if empty or unparsable.
     */
    public static function toDMY($date): string
    {
        if (empty($date)) {
            return '';
        }

        try {
            return Carbon::parse($date)->format('d-m-Y');
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Same, but includes time — for published_at, created_at, registered_on.
     */
    public static function toDMYWithTime($date): string
    {
        if (empty($date)) {
            return '';
        }

        try {
            return Carbon::parse($date)->format('d-m-Y h:i A');
        } catch (\Exception $e) {
            return '';
        }
    }
}