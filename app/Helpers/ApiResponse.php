<?php

namespace App\Helpers;

use Carbon\Carbon;

class ApiResponse
{
    // Success response
    public static function success(
        $data    = null,
        $message = 'Success',
        $code    = 200
    ) {
        return response()->json([
            'status'      => '1',
            'status_code' => (string) $code,
            'message'     => $message,
            'data'        => self::formatData($data)
        ], $code);
    }

    // Error response
    public static function error(
    $message = 'Something went wrong',
    $code    = 400,
    $errors  = null
) {
    $response = [
        'status'      => '0',
        'status_code' => (string) $code,
        'message'     => $message,
        'data'        => ""   // was: null
    ];

    if ($errors) {
        $response['errors'] = $errors;
    }

    return response()->json($response, $code);
}

    // Paginated response
    public static function paginated(
        $paginator,
        $message = 'Data fetched successfully'
    ) {
        return response()->json([
            'status'      => '1',
            'status_code' => '200',
            'message'     => $message,
            'data'        => self::formatData(
                                collect($paginator->items())
                             ),
            'pagination'  => [
                'current_page' => (string) $paginator->currentPage(),
                'per_page'     => (string) $paginator->perPage(),
                'total_pages'  => (string) $paginator->lastPage(),
                'total_items'  => (string) $paginator->total(),
            ]
        ], 200);
    }

    // ─────────────────────────────────
    // PRIVATE METHODS
    // ─────────────────────────────────

    private static function formatData($data)
    {
        if (is_null($data)) {
            return (object) [];
        }

        // Laravel Collection → list of items
        if ($data instanceof \Illuminate\Support\Collection) {
            return $data->map(function ($item) {
                return self::formatItem($item);
            })->values();
        }

        // Eloquent model → single item
        if ($data instanceof \Illuminate\Database\Eloquent\Model) {
            return self::formatItem($data);
        }

        // Indexed array → list of items
        // Example: [0 => [...], 1 => [...]]
        if (is_array($data) && array_keys($data) === range(0, count($data) - 1)) {
            return collect($data)->map(function ($item) {
                return self::formatItem($item);
            })->values();
        }

        // Associative array → single item
        // Example: ['id' => 1, 'name' => 'John']
        if (is_array($data)) {
            return self::formatItem($data);
        }

        // Any other single item
        return self::formatItem($data);
    }

    private static function formatItem($item)
    {
        // Eloquent model → convert to array
        if (
            is_object($item) &&
            method_exists($item, 'toArray')
        ) {
            $array = $item->toArray();
        }
        // Already array
        elseif (is_array($item)) {
            $array = $item;
        }
        // Any other object
        else {
            $array = (array) $item;
        }

        // Format created_at to IST
        if (isset($array['created_at']) && $array['created_at']) {
            $array['created_at'] = Carbon::parse(
                $array['created_at']
            )->timezone('Asia/Kolkata')
             ->format('Y-m-d H:i:s');
        }

        // Format updated_at to IST
        if (isset($array['updated_at']) && $array['updated_at']) {
            $array['updated_at'] = Carbon::parse(
                $array['updated_at']
            )->timezone('Asia/Kolkata')
             ->format('Y-m-d H:i:s');
        }

        // Put id first
        $ordered = [];
        if (isset($array['id'])) {
            $ordered['id'] = $array['id'];
            unset($array['id']);
        }

        return array_merge($ordered, $array);
    }
}