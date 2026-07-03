<?php

namespace Modules\TripManagement\Http\Controllers\Api\Driver;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\TripManagement\Entities\TempTripNotification;
use Modules\TripManagement\Entities\TripRequest;
use Modules\TripManagement\Http\Controllers\Concerns\ChecksDriverApproval;

class VitoParcelController extends Controller
{
    use ChecksDriverApproval;

    public function atomicAcceptParcel(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'trip_request_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(responseFormatter(constant: DEFAULT_400, errors: errorProcessor($validator)), 400);
        }

        // Only verified, non-suspended drivers may accept parcels.
        if (!$this->driverApproved($request->user()->driverDetails)) {
            return response()->json(responseFormatter(DEFAULT_403), 403);
        }

        $result = DB::transaction(function () use ($request) {
            $trip = TripRequest::where('id', $request->trip_request_id)
                ->where('current_status', 'pending')
                ->where('type', 'parcel')
                ->whereNull('driver_id')
                ->lockForUpdate()
                ->first();

            if (!$trip) {
                return null;
            }

            $trip->driver_id = $request->user()->id;
            $trip->current_status = 'accepted';
            $trip->save();

            TempTripNotification::where('trip_request_id', $trip->id)->delete();

            return $trip;
        });

        if (!$result) {
            return response()->json(responseFormatter(constant: TRIP_REQUEST_404), 404);
        }

        // Reload with customer relation for notification
        $trip = TripRequest::with('customer')->find($result->id);

        // Notify customer that driver accepted the parcel
        try {
            $push = getNotification('parcel_accepted');
            if ($push) {
                sendDeviceNotification(
                    fcm_token: $trip->customer->fcm_token,
                    title: translate(key: $push['title'], locale: $trip->customer->current_language_key),
                    description: textVariableDataFormat(
                        value: $push['description'],
                        tripId: $trip->ref_id,
                        sentTime: pushSentTime($trip->updated_at),
                        locale: $trip->customer->current_language_key
                    ),
                    status: $push['status'],
                    ride_request_id: $trip->id,
                    type: 'parcel',
                    notification_type: 'parcel',
                    action: $push['action'],
                    user_id: $trip->customer_id
                );
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Push notification failed in atomicAcceptParcel: ' . $e->getMessage());
        }

        return response()->json(responseFormatter(DEFAULT_200, $result));
    }
}
