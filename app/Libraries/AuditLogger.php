<?php

namespace App\Libraries;

use App\Models\QuotationAuditLogModel;
use App\Models\ConsigneeModel;
use App\Models\UserModel;

class AuditLogger
{
    protected $auditModel;
    protected $consigneeModel;
    protected $userModel;
    protected $request;

    public function __construct()
    {
        $this->auditModel = new QuotationAuditLogModel();
        $this->consigneeModel = new ConsigneeModel();
        $this->userModel = new UserModel();
        $this->request = \Config\Services::request();
    }

    /**
     * Log hotel/date changes
     */
    public function logHotelDateChange($tourId, $action, $oldData = null, $newData = [], $userId = null)
    {
        $tourDetails = $this->getTourDetails($tourId);
        $userDetails = $this->getUserDetails($userId);

        $changeDescription = $this->buildHotelDateDescription($action, $oldData, $newData);

        $logData = [
            'tour_id' => $tourId,
            'tour_manual_id' => $tourDetails['manual_id'] ?? null,
            'consignee_name' => $tourDetails['consignee'] ?? null,
            'change_type' => 'hotel_date',
            'action_type' => $action,
            'table_affected' => 'tour_quotations',
            'record_id' => $newData['trans_row_id'] ?? ($oldData['row_id'] ?? null),
            'quotation_date' => $newData['t_date'] ?? ($oldData['tour_date'] ?? null),
            'quotation_row_id' => $newData['trans_row_id'] ?? ($oldData['row_id'] ?? null),
            'field_changed' => 'hotel_details',
            'old_value' => $oldData ? json_encode($oldData) : null,
            'new_value' => json_encode($newData),
            'full_data_snapshot' => json_encode([
                'before' => $oldData,
                'after' => $newData
            ]),
            'change_description' => $changeDescription,
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => $this->request->getUserAgent()->__toString(),
            'user_id' => $userDetails['user_id'],
            'user_name' => $userDetails['user_name'],
            'created_at' => date('Y-m-d H:i:s')
        ];

        return $this->auditModel->insert($logData);
    }

    /**
     * Log sub-location changes
     */
    public function logSubLocationChange($tourId, $action, $oldData = null, $newData = [], $userId = null)
    {
        $tourDetails = $this->getTourDetails($tourId);
        $userDetails = $this->getUserDetails($userId);

        $changeDescription = $this->buildSubLocationDescription($action, $oldData, $newData);

        $logData = [
            'tour_id' => $tourId,
            'tour_manual_id' => $tourDetails['manual_id'] ?? null,
            'consignee_name' => $tourDetails['consignee'] ?? null,
            'change_type' => 'sub_location',
            'action_type' => $action,
            'table_affected' => 'tour_sub_locations',
            'record_id' => $newData['id'] ?? ($oldData['id'] ?? null),
            'quotation_date' => $newData['date_id'] ?? ($oldData['date_id'] ?? null),
            'field_changed' => 'sub_location',
            'old_value' => $oldData ? json_encode($oldData) : null,
            'new_value' => json_encode($newData),
            'full_data_snapshot' => json_encode([
                'before' => $oldData,
                'after' => $newData
            ]),
            'change_description' => $changeDescription,
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => $this->request->getUserAgent()->__toString(),
            'user_id' => $userDetails['user_id'],
            'user_name' => $userDetails['user_name'],
            'created_at' => date('Y-m-d H:i:s')
        ];

        return $this->auditModel->insert($logData);
    }

    /**
     * Log activity changes
     */
    public function logActivityChange($tourId, $action, $oldData = null, $newData = [], $userId = null)
    {
        $tourDetails = $this->getTourDetails($tourId);
        $userDetails = $this->getUserDetails($userId);

        $changeDescription = $this->buildActivityDescription($action, $oldData, $newData);

        $logData = [
            'tour_id' => $tourId,
            'tour_manual_id' => $tourDetails['manual_id'] ?? null,
            'consignee_name' => $tourDetails['consignee'] ?? null,
            'change_type' => 'activity',
            'action_type' => $action,
            'table_affected' => 'tour_activity',
            'record_id' => $newData['id'] ?? ($oldData['id'] ?? null),
            'quotation_date' => $newData['tour_date'] ?? ($oldData['tour_date'] ?? null),
            'field_changed' => 'activity',
            'old_value' => $oldData ? json_encode($oldData) : null,
            'new_value' => json_encode($newData),
            'full_data_snapshot' => json_encode([
                'before' => $oldData,
                'after' => $newData
            ]),
            'change_description' => $changeDescription,
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => $this->request->getUserAgent()->__toString(),
            'user_id' => $userDetails['user_id'],
            'user_name' => $userDetails['user_name'],
            'created_at' => date('Y-m-d H:i:s')
        ];

        return $this->auditModel->insert($logData);
    }

    /**
     * Log itinerary changes
     */
    public function logItineraryChange($tourId, $action, $oldData = null, $newData = [], $userId = null)
    {
        $tourDetails = $this->getTourDetails($tourId);
        $userDetails = $this->getUserDetails($userId);

        $changeDescription = $this->buildItineraryDescription($action, $oldData, $newData);

        $logData = [
            'tour_id' => $tourId,
            'tour_manual_id' => $tourDetails['manual_id'] ?? null,
            'consignee_name' => $tourDetails['consignee'] ?? null,
            'change_type' => 'itinerary',
            'action_type' => $action,
            'table_affected' => 'tour_itinerary',
            'record_id' => $newData['id'] ?? ($oldData['id'] ?? null),
            'quotation_date' => $newData['date'] ?? ($oldData['date'] ?? null),
            'field_changed' => 'itinerary',
            'old_value' => $oldData ? json_encode($oldData) : null,
            'new_value' => json_encode($newData),
            'full_data_snapshot' => json_encode([
                'before' => $oldData,
                'after' => $newData
            ]),
            'change_description' => $changeDescription,
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => $this->request->getUserAgent()->__toString(),
            'user_id' => $userDetails['user_id'],
            'user_name' => $userDetails['user_name'],
            'created_at' => date('Y-m-d H:i:s')
        ];

        return $this->auditModel->insert($logData);
    }

    /**
     * Log general updates (driver/guide)
     */
    public function logGeneralUpdate($tourId, $oldData, $newData, $userId = null)
    {
        $tourDetails = $this->getTourDetails($tourId);
        $userDetails = $this->getUserDetails($userId);

        $changeDescription = $this->buildGeneralUpdateDescription($oldData, $newData);

        $logData = [
            'tour_id' => $tourId,
            'tour_manual_id' => $tourDetails['manual_id'] ?? null,
            'consignee_name' => $tourDetails['consignee'] ?? null,
            'change_type' => 'general',
            'action_type' => 'UPDATE',
            'table_affected' => 'consignee',
            'record_id' => $tourId,
            'field_changed' => 'driver_guide',
            'old_value' => json_encode($oldData),
            'new_value' => json_encode($newData),
            'full_data_snapshot' => json_encode([
                'before' => $oldData,
                'after' => $newData
            ]),
            'change_description' => $changeDescription,
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => $this->request->getUserAgent()->__toString(),
            'user_id' => $userDetails['user_id'],
            'user_name' => $userDetails['user_name'],
            'created_at' => date('Y-m-d H:i:s')
        ];

        return $this->auditModel->insert($logData);
    }

    /**
     * Log tour creation
     */
    public function logTourCreation($tourId, $tourData, $userId = null)
    {
        $userDetails = $this->getUserDetails($userId);

        // Build a description with key tour details
        $changeDescription = sprintf(
            'Created new tour: %s | Dates: %s to %s | Passengers: %s | Agent: %s',
            $tourData['consignee'] ?? 'N/A',
            $tourData['start_date'] ?? 'N/A',
            $tourData['end_date'] ?? 'N/A',
            $tourData['passenger_count'] ?? 'N/A',
            $tourData['agent_name'] ?? 'N/A'
        );

        $logData = [
            'tour_id' => $tourId,
            'tour_manual_id' => $tourData['manual_id'] ?? null,
            'consignee_name' => $tourData['consignee'] ?? null,
            'change_type' => 'tour_creation',
            'action_type' => 'INSERT',
            'table_affected' => 'consignee',
            'record_id' => $tourId,
            'field_changed' => 'tour',
            'old_value' => null,
            'new_value' => json_encode($tourData),
            'full_data_snapshot' => json_encode([
                'before' => null,
                'after' => $tourData
            ]),
            'change_description' => $changeDescription,
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => $this->request->getUserAgent()->__toString(),
            'user_id' => $userDetails['user_id'],
            'user_name' => $userDetails['user_name'],
            'created_at' => date('Y-m-d H:i:s')
        ];

        return $this->auditModel->insert($logData);
    }

    /**
     * Log room count changes
     */
    public function logRoomCountChange($tourId, $oldData, $newData, $userId = null)
    {
        $tourDetails = $this->getTourDetails($tourId);
        $userDetails = $this->getUserDetails($userId);

        $changeDescription = $this->buildRoomCountDescription($oldData, $newData);

        $logData = [
            'tour_id' => $tourId,
            'tour_manual_id' => $tourDetails['manual_id'] ?? null,
            'consignee_name' => $tourDetails['consignee'] ?? null,
            'change_type' => 'room_count',
            'action_type' => 'UPDATE',
            'table_affected' => 'consignee',
            'record_id' => $tourId,
            'field_changed' => 'room_count',
            'old_value' => json_encode($oldData),
            'new_value' => json_encode($newData),
            'full_data_snapshot' => json_encode([
                'before' => $oldData,
                'after' => $newData
            ]),
            'change_description' => $changeDescription,
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => $this->request->getUserAgent()->__toString(),
            'user_id' => $userDetails['user_id'],
            'user_name' => $userDetails['user_name'],
            'created_at' => date('Y-m-d H:i:s')
        ];

        return $this->auditModel->insert($logData);
    }

    /**
     * Log vehicle rate changes
     */
    public function logVehicleRateChange($tourId, $oldData, $newData, $userId = null)
    {
        $tourDetails = $this->getTourDetails($tourId);
        $userDetails = $this->getUserDetails($userId);

        $changeDescription = $this->buildVehicleRateDescription($oldData, $newData);

        $logData = [
            'tour_id' => $tourId,
            'tour_manual_id' => $tourDetails['manual_id'] ?? null,
            'consignee_name' => $tourDetails['consignee'] ?? null,
            'change_type' => 'vehicle_rate',
            'action_type' => 'UPDATE',
            'table_affected' => 'consignee',
            'record_id' => $tourId,
            'field_changed' => 'vehicle_rate',
            'old_value' => json_encode($oldData),
            'new_value' => json_encode($newData),
            'full_data_snapshot' => json_encode([
                'before' => $oldData,
                'after' => $newData
            ]),
            'change_description' => $changeDescription,
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => $this->request->getUserAgent()->__toString(),
            'user_id' => $userDetails['user_id'],
            'user_name' => $userDetails['user_name'],
            'created_at' => date('Y-m-d H:i:s')
        ];

        return $this->auditModel->insert($logData);
    }

    /**
     * Log passenger changes
     */
    public function logPassengerChange($tourId, $action, $oldData = null, $newData = [], $userId = null)
    {
        $tourDetails = $this->getTourDetails($tourId);
        $userDetails = $this->getUserDetails($userId);

        $changeDescription = $this->buildPassengerDescription($action, $oldData, $newData);

        $logData = [
            'tour_id' => $tourId,
            'tour_manual_id' => $tourDetails['manual_id'] ?? null,
            'consignee_name' => $tourDetails['consignee'] ?? null,
            'change_type' => 'passenger',
            'action_type' => $action,
            'table_affected' => 'passenger_details',
            'record_id' => $newData['id'] ?? ($oldData['id'] ?? null),
            'field_changed' => 'passenger',
            'old_value' => $oldData ? json_encode($oldData) : null,
            'new_value' => json_encode($newData),
            'full_data_snapshot' => json_encode([
                'before' => $oldData,
                'after' => $newData
            ]),
            'change_description' => $changeDescription,
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => $this->request->getUserAgent()->__toString(),
            'user_id' => $userDetails['user_id'],
            'user_name' => $userDetails['user_name'],
            'created_at' => date('Y-m-d H:i:s')
        ];

        return $this->auditModel->insert($logData);
    }

    /**
     * Get tour details for logging
     */
    private function getTourDetails($tourId)
    {
        $tour = $this->consigneeModel->where('tour_id', $tourId)->first();
        return [
            'manual_id' => $tour['manual_id'] ?? null,
            'consignee' => $tour['consignee'] ?? null
        ];
    }

    /**
     * Get user details for logging
     */
    private function getUserDetails($userId = null)
    {
        // Try to get from session first (session stores as 'username' and 'user_id')
        $sessionUserId = session()->get('user_id') ?? session()->get('ID') ?? session()->get('id');
        $sessionUserName = session()->get('username') ?? session()->get('ID') ?? session()->get('user');

        // Use provided userId or session userId
        $finalUserId = $userId ?? $sessionUserId ?? 'UNKNOWN';

        // Always fetch from database if we have a valid user ID to ensure accuracy
        if ($finalUserId !== 'UNKNOWN') {
            $user = $this->userModel->find($finalUserId);
            if ($user) {
                $sessionUserName = $user['user'] ?? $sessionUserName;
            }
        }

        return [
            'user_id' => $finalUserId,
            'user_name' => $sessionUserName ?? 'Unknown User'
        ];
    }

    /**
     * Build description for hotel/date changes
     */
    private function buildHotelDateDescription($action, $oldData, $newData)
    {
        if ($action === 'INSERT') {
            return sprintf(
                'Added hotel: %s, Room Type: %s, Meal: %s on %s',
                $newData['hotel_name'] ?? 'N/A',
                $newData['room_type'] ?? 'N/A',
                $newData['meal_code'] ?? 'N/A',
                $newData['t_date'] ?? 'N/A'
            );
        } elseif ($action === 'UPDATE') {
            $changes = [];

            // Check hotel change
            if (isset($oldData['hotel_acc'], $newData['hotel']) && $oldData['hotel_acc'] != $newData['hotel']) {
                $changes[] = sprintf('Hotel: %s → %s', $oldData['hotel_name'] ?? 'N/A', $newData['hotel_name'] ?? 'N/A');
            }

            // Check room type change
            if (isset($oldData['room_type'], $newData['room_type']) && $oldData['room_type'] != $newData['room_type']) {
                $changes[] = sprintf('Room Type: %s → %s', $oldData['room_type'] ?? 'N/A', $newData['room_type'] ?? 'N/A');
            }

            // Check meal code change
            if (isset($oldData['meal_code'], $newData['meal_code']) && $oldData['meal_code'] != $newData['meal_code']) {
                $changes[] = sprintf('Meal: %s → %s', $oldData['meal_code'] ?? 'N/A', $newData['meal_code'] ?? 'N/A');
            }

            // Check SGL price change
            if (isset($oldData['sgl'], $newData['sgl']) && $oldData['sgl'] != $newData['sgl']) {
                $changes[] = sprintf('SGL Price: %s → %s', $oldData['sgl'], $newData['sgl']);
            }

            // Check DBL price change
            if (isset($oldData['dbl'], $newData['dbl']) && $oldData['dbl'] != $newData['dbl']) {
                $changes[] = sprintf('DBL Price: %s → %s', $oldData['dbl'], $newData['dbl']);
            }

            // Check TPL price change
            if (isset($oldData['tpl'], $newData['tpl']) && $oldData['tpl'] != $newData['tpl']) {
                $changes[] = sprintf('TPL Price: %s → %s', $oldData['tpl'], $newData['tpl']);
            }

            // Check SGL FOC change
            if (isset($oldData['sglfoc'], $newData['sglfoc']) && $oldData['sglfoc'] != $newData['sglfoc']) {
                $changes[] = sprintf('SGL FOC: %s → %s', $oldData['sglfoc'], $newData['sglfoc']);
            }

            // Check DBL FOC change
            if (isset($oldData['dblfoc'], $newData['dblfoc']) && $oldData['dblfoc'] != $newData['dblfoc']) {
                $changes[] = sprintf('DBL FOC: %s → %s', $oldData['dblfoc'], $newData['dblfoc']);
            }

            // Check TPL FOC change
            if (isset($oldData['tplfoc'], $newData['tplfoc']) && $oldData['tplfoc'] != $newData['tplfoc']) {
                $changes[] = sprintf('TPL FOC: %s → %s', $oldData['tplfoc'], $newData['tplfoc']);
            }

            if (empty($changes)) {
                return 'Updated on ' . ($newData['t_date'] ?? 'N/A');
            }

            return 'Updated ' . implode(', ', $changes) . ' on ' . ($newData['t_date'] ?? 'N/A');
        } elseif ($action === 'DELETE') {
            return sprintf(
                'Deleted hotel record for %s',
                $oldData['tour_date'] ?? 'N/A'
            );
        }
        return 'Hotel/Date change';
    }

    /**
     * Build description for sub-location changes
     */
    private function buildSubLocationDescription($action, $oldData, $newData)
    {
        if ($action === 'INSERT') {
            return sprintf(
                'Added sub-location: %s',
                $newData['location_name'] ?? 'N/A'
            );
        } elseif ($action === 'DELETE') {
            return sprintf(
                'Deleted sub-location: %s',
                $oldData['location_name'] ?? 'N/A'
            );
        }
        return 'Sub-location change';
    }

    /**
     * Build description for activity changes
     */
    private function buildActivityDescription($action, $oldData, $newData)
    {
        if ($action === 'INSERT') {
            // Use place_name or name field, and fee field
            $activityName = $newData['place_name'] ?? $newData['name'] ?? 'N/A';
            $fee = $newData['fee'] ?? '0';
            return sprintf(
                'Added activity: %s (Fee: %s)',
                $activityName,
                $fee
            );
        } elseif ($action === 'DELETE') {
            // Use place_name or name field
            $activityName = $oldData['place_name'] ?? $oldData['name'] ?? 'N/A';
            return sprintf(
                'Deleted activity: %s',
                $activityName
            );
        }
        return 'Activity change';
    }

    /**
     * Build description for itinerary changes
     */
    private function buildItineraryDescription($action, $oldData, $newData)
    {
        if ($action === 'INSERT') {
            return sprintf(
                'Added itinerary for %s',
                $newData['date'] ?? 'N/A'
            );
        } elseif ($action === 'DELETE') {
            return sprintf(
                'Deleted itinerary for %s',
                $oldData['date'] ?? 'N/A'
            );
        }
        return 'Itinerary change';
    }

    /**
     * Build description for general updates
     */
    private function buildGeneralUpdateDescription($oldData, $newData)
    {
        $changes = [];
        if (isset($oldData['vehicle_name'], $newData['vehicle_name']) && $oldData['vehicle_name'] != $newData['vehicle_name']) {
            $changes[] = sprintf('Vehicle: %s → %s', $oldData['vehicle_name'], $newData['vehicle_name']);
        }
        if (isset($oldData['guide_name'], $newData['guide_name']) && $oldData['guide_name'] != $newData['guide_name']) {
            $changes[] = sprintf('Guide: %s → %s', $oldData['guide_name'], $newData['guide_name']);
        }
        return 'Updated ' . implode(', ', $changes);
    }

    /**
     * Build description for room count changes
     */
    private function buildRoomCountDescription($oldData, $newData)
    {
        $changes = [];
        if (isset($oldData['sgl'], $newData['sgl']) && $oldData['sgl'] != $newData['sgl']) {
            $changes[] = sprintf('Single: %s → %s', $oldData['sgl'], $newData['sgl']);
        }
        if (isset($oldData['dbl'], $newData['dbl']) && $oldData['dbl'] != $newData['dbl']) {
            $changes[] = sprintf('Double: %s → %s', $oldData['dbl'], $newData['dbl']);
        }
        if (isset($oldData['tpl'], $newData['tpl']) && $oldData['tpl'] != $newData['tpl']) {
            $changes[] = sprintf('Triple: %s → %s', $oldData['tpl'], $newData['tpl']);
        }
        return 'Updated room count: ' . implode(', ', $changes);
    }

    /**
     * Build description for vehicle rate changes
     */
    private function buildVehicleRateDescription($oldData, $newData)
    {
        return sprintf(
            'Updated vehicle rate: %s → %s',
            $oldData['vehicle_rate'] ?? '0',
            $newData['vehicle_rate'] ?? '0'
        );
    }

    /**
     * Build description for passenger changes
     */
    private function buildPassengerDescription($action, $oldData, $newData)
    {
        if ($action === 'INSERT') {
            return sprintf(
                'Added passenger: %s %s',
                $newData['first_name'] ?? '',
                $newData['last_name'] ?? ''
            );
        } elseif ($action === 'DELETE') {
            return sprintf(
                'Deleted passenger: %s %s',
                $oldData['first_name'] ?? '',
                $oldData['last_name'] ?? ''
            );
        }
        return 'Passenger change';
    }
}
