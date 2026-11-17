<?php

namespace App\Services;

use App\Models\DistrictAdmin;
use App\Models\GalleryImage;
use App\Models\MemberProfile;
use App\Models\News;
use App\Models\NewsRead;
use App\Models\UnitAdmin;

class ContentVisibility
{
    private function scope(int $userId): array
    {
        $profile = MemberProfile::where('user_id', $userId)->first();

        $scoped = DistrictAdmin::pluck('user_id')
            ->merge(UnitAdmin::pluck('user_id'))->unique()->values()->all();

        $district = $profile
            ? DistrictAdmin::where('district_id', $profile->district_id)->pluck('user_id')->all() : [];
        $unit = $profile
            ? UnitAdmin::where('unit_id', $profile->unit_id)->pluck('user_id')->all() : [];

        return [$profile, $scoped, $district, $unit];
    }

    public function news(int $userId)
    {
        [, $scoped, $district, $unit] = $this->scope($userId);

        return News::where('status', 'PUBLISHED')
            ->where(function ($q) use ($scoped, $district, $unit) {
                $q->whereHas('createdBy', fn($u) => $u->whereNotIn('id', $scoped))
                  ->orWhereIn('created_by', $district)
                  ->orWhereIn('created_by', $unit);
            });
    }

    public function gallery(int $userId)
    {
        [, $scoped, $district, $unit] = $this->scope($userId);

        return GalleryImage::where('is_active', true)
            ->where(function ($q) use ($scoped, $district, $unit) {
                $q->whereNotIn('created_by', $scoped)
                  ->orWhereIn('created_by', $district)
                  ->orWhereIn('created_by', $unit);
            });
    }

    public function unreadNewsCount(int $userId): int
    {
        return $this->news($userId)
            ->whereNotIn('id', NewsRead::where('user_id', $userId)->select('news_id'))
            ->count();
    }

    public function unreadGalleryCount(int $userId): int
    {
        [$profile] = $this->scope($userId);

        $query = $this->gallery($userId);

        if ($profile && $profile->gallery_last_seen_at) {
            $query->where('created_at', '>', $profile->gallery_last_seen_at);
        }

        return $query->count();   // never seen the gallery = everything is unread
    }
}