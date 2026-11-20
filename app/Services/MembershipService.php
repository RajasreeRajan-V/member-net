<?php

namespace App\Services;

use App\Models\MemberProfile;
use App\Models\DeletedMember;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MembershipService
{
    public function approve(MemberProfile $profile): MemberProfile
    {
        return DB::transaction(function () use ($profile) {
            $profile = MemberProfile::with(['user', 'unit', 'district'])
                ->lockForUpdate()
                ->findOrFail($profile->id);

            // An ID never changes once issued (e.g. rejected, then re-approved)
            if (!$profile->membership_id) {
                $profile->membership_id = $this->nextMembershipId($profile);
            }

            $profile->status      = 'APPROVED';
            $profile->joined_date = $profile->joined_date ?? Carbon::now()->toDateString();
            $profile->save();

            return $profile;
        });
    }

    /**
     * Generate unique member ID:
     * Format: {3_LETTERS_OF_UNIT}/{4_CHAR_MEMBER_CODE}/{3_DIGIT_SERIAL}
     * Example: Unit "Thalassery", Name "Rajesh", DOB "20-05-1995" -> THA/RJ20/001
     */
    public function nextMembershipId(MemberProfile $profile): string
    {
        $profile->loadMissing(['unit', 'unit.district', 'district', 'user']);

        $unit = $profile->unit;
        if (!$unit && $profile->unit_id) {
            $unit = Unit::find($profile->unit_id);
        }

        $userName = $profile->user?->name;
        if (!$userName && $profile->user_id) {
            $userName = \App\Models\User::find($profile->user_id)?->name;
        }

        $dob = $profile->dob;

        return self::generateIdForMember($unit, $userName, $dob, $profile->district?->name);
    }

    /**
     * Generate unique member ID given Unit, User Name, DOB and fallback unit name.
     * Format: {UNIT_CODE}/{MEMBER_CODE}/{SERIAL} (e.g. THA/RJ20/001)
     */
    public static function generateIdForMember(?Unit $unit, ?string $userName, ?string $dob = null, ?string $fallbackUnitName = null): string
    {
        // 1. First Section: Unit Code (3 Letters uppercase, e.g. THA)
        $unitPrefix = null;
        if ($unit) {
            if (!empty($unit->unit_id) && strlen($unit->unit_id) >= 3) {
                $unitPrefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $unit->unit_id), 0, 3));
            } elseif (!empty($unit->name)) {
                $unitPrefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $unit->name), 0, 3));
            }
        }

        if (!$unitPrefix && $fallbackUnitName) {
            $unitPrefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $fallbackUnitName), 0, 3));
        }

        if (!$unitPrefix || strlen($unitPrefix) < 3) {
            $unitPrefix = str_pad($unitPrefix ?? '', 3, 'X');
        }

        // 2. Second Section: Member Code (4 Characters: Name letters + DOB digits, e.g. RJ20)
        $memberCode = self::generateMemberCode($userName, $dob);

        // 3. Third Section: Sequential numbering starting from 001, incremented per unit
        $unitSearchPrefix = $unitPrefix . '/';

        $existing = MemberProfile::withTrashed()
            ->where('membership_id', 'LIKE', $unitSearchPrefix . '%')
            ->pluck('membership_id');

        $deleted = DeletedMember::where('membership_id', 'LIKE', $unitSearchPrefix . '%')
            ->pluck('membership_id');

        $all = $existing->concat($deleted);

        $maxSerial = 0;
        foreach ($all as $mid) {
            $parts = explode('/', (string) $mid);
            $lastPart = end($parts);
            if (is_numeric($lastPart)) {
                $serial = (int) $lastPart;
                if ($serial > $maxSerial) {
                    $maxSerial = $serial;
                }
            }
        }

        $seq = $maxSerial + 1;
        do {
            $candidate = $unitPrefix . '/' . $memberCode . '/' . str_pad($seq, 3, '0', STR_PAD_LEFT);
            $seq++;
        } while (
            MemberProfile::withTrashed()->where('membership_id', $candidate)->exists()
            || DeletedMember::where('membership_id', $candidate)->exists()
        );

        return $candidate;
    }

    /**
     * Generate 4-character Member Code using member's first name and date of birth.
     * Example: Name "Rajesh", DOB "20-05-1995" -> "RJ20"
     */
    public static function generateMemberCode(?string $name, ?string $dob = null): string
    {
        $cleanName = strtoupper(preg_replace('/[^A-Za-z]/', '', (string) $name));

        // 1st letter of name
        $firstLetter = $cleanName[0] ?? 'M';

        // 2nd letter: next consonant after first letter, or 2nd letter of name
        $secondLetter = null;
        $len = strlen($cleanName);
        if ($len > 1) {
            for ($i = 1; $i < $len; $i++) {
                $char = $cleanName[$i];
                if (!in_array($char, ['A', 'E', 'I', 'O', 'U'])) {
                    $secondLetter = $char;
                    break;
                }
            }
            if (!$secondLetter) {
                $secondLetter = $cleanName[1];
            }
        } else {
            $secondLetter = 'X';
        }

        $letters = $firstLetter . $secondLetter;

        // DOB Day (2 digits, e.g. 20 from 20-05-1995 or 1995-05-20)
        $dayDigits = '01';
        if (!empty($dob)) {
            try {
                $time = strtotime($dob);
                if ($time !== false) {
                    $dayDigits = date('d', $time);
                } else {
                    $digitsOnly = preg_replace('/[^0-9]/', '', (string) $dob);
                    $dayDigits = strlen($digitsOnly) >= 2 ? substr($digitsOnly, 0, 2) : '01';
                }
            } catch (\Throwable $e) {
                $dayDigits = '01';
            }
        }

        return $letters . $dayDigits;
    }

    /**
     * Backward-compatible helper for unit-only calls.
     */
    public static function generateForUnit(?Unit $unit, ?string $userName = null, ?string $dob = null, ?string $fallbackName = null): string
    {
        return self::generateIdForMember($unit, $userName ?? 'Rajesh', $dob ?? '1995-05-20', $fallbackName);
    }

    /**
     * Backward-compatible helper for unit name strings.
     */
    public static function generateUniqueId(?string $unitName, ?string $userName = null, ?string $dob = null): string
    {
        $unit = $unitName ? Unit::where('name', $unitName)->first() : null;
        return self::generateIdForMember($unit, $userName ?? 'Rajesh', $dob ?? '1995-05-20', $unitName);
    }
}
