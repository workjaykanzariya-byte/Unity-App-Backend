<?php

namespace App\Http\Controllers\Notifications;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notifications\MarkAllNotificationsReadRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use stdClass;

class NotificationController extends Controller
{
    private const TABLE = 'notification_data';

    public function markAllRead(MarkAllNotificationsReadRequest $request): JsonResponse
    {
        if (! Schema::hasTable(self::TABLE) || ! Schema::hasColumn(self::TABLE, 'is_read')) {
            return response()->json([
                'status' => 'error',
                'data' => null,
                'meta' => new stdClass(),
                'errors' => [
                    [
                        'code' => 'NOTIFICATION_TABLE_NOT_CONFIGURED',
                        'message' => 'The notification_data table with an is_read flag is required.',
                    ],
                ],
            ], 500);
        }

        $validated = $request->validated();
        $query = DB::table(self::TABLE)->where('is_read', false);

        if (! empty($validated['user_id']) && Schema::hasColumn(self::TABLE, 'user_id')) {
            $query->where('user_id', $validated['user_id']);
        }

        $updates = ['is_read' => true];

        if (Schema::hasColumn(self::TABLE, 'updated_at')) {
            $updates['updated_at'] = now();
        }

        $updatedCount = $query->update($updates);

        return response()->json([
            'status' => 'success',
            'data' => [
                'marked_read_count' => $updatedCount,
            ],
            'meta' => new stdClass(),
            'errors' => null,
        ]);
    }
}
