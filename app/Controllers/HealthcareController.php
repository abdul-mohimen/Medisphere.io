<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Services\SubscriptionAccess;

class HealthcareController
{
    public function find(): void
    {
        $subscriptionAccess = new SubscriptionAccess();
        $subscriptionLocked = !$subscriptionAccess->canAccessLevel('basic', Auth::check() && Auth::type() === 'patient' ? Auth::id() : null);

        $maxFee = trim((string) ($_GET['max_fee'] ?? ''));
        if ($maxFee !== '' && (!is_numeric($maxFee) || (float) $maxFee < 0)) {
            $maxFee = '';
        }

        $hospitalId = trim((string) ($_GET['hospital_id'] ?? ''));
        if ($hospitalId !== '' && (!ctype_digit($hospitalId) || (int) $hospitalId < 1)) {
            $hospitalId = '';
        }

        $filters = [
            'specialization' => trim((string) ($_GET['specialization'] ?? '')),
            'location' => trim((string) ($_GET['location'] ?? '')),
            'hospital_id' => $hospitalId,
            'max_fee' => $maxFee,
            'active_only' => true,
        ];

        View::render('find_healthcare', [
            'title' => 'Find Healthcare',
            'doctors' => (new Doctor())->search($filters),
            'hospitals' => (new Hospital())->all(),
            'filters' => $filters,
            'subscriptionLocked' => $subscriptionLocked,
            ...$subscriptionAccess->modalData(
                'Advanced doctor and hospital access',
                'Choose Lite, Pro, or Advanced access for provider comparison and live discovery tools.',
                route_url('find-healthcare'),
                false
            ),
        ]);
    }

    public function facilitiesApi(): void
    {
        header('Content-Type: application/json');

        if (!(new SubscriptionAccess())->canAccessLevel('basic', Auth::check() && Auth::type() === 'patient' ? Auth::id() : null)) {
            http_response_code(402);
            echo json_encode([
                'success' => false,
                'premium_required' => true,
                'message' => 'Lite, Pro, or Advanced subscription required for advanced doctor and hospital data.',
            ]);
            return;
        }

        $hospitals = (new Hospital())->all();
        $doctors = (new Doctor())->search(['active_only' => true]);

        $payload = [
            'hospitals' => array_map(function (array $hospital) {
                return [
                    'id' => (int) $hospital['id'],
                    'type' => 'hospital',
                    'name' => $hospital['name'],
                    'address' => $hospital['address'],
                    'city' => $hospital['city'],
                    'country' => $hospital['country'],
                    'phone' => $hospital['phone'],
                    'email' => $hospital['email'],
                    'facilities' => $hospital['facilities'],
                    'verified_status' => $hospital['verified_status'],
                    'coordinates' => $hospital['coordinates'],
                ];
            }, $hospitals),
            'doctors' => array_map(function (array $doctor) {
                return [
                    'id' => (int) $doctor['user_id'],
                    'type' => 'doctor',
                    'name' => $doctor['name'],
                    'specialization' => $doctor['specialization'],
                    'experience' => (int) $doctor['experience'],
                    'consultation_fee' => (float) $doctor['consultation_fee'],
                    'verified_status' => $doctor['verified_status'],
                    'hospital_id' => $doctor['hospital_id'] ? (int) $doctor['hospital_id'] : null,
                    'hospital_name' => $doctor['hospital_name'],
                    'hospital_coordinates' => $doctor['hospital_coordinates'],
                    'hospital_address' => $doctor['hospital_address'],
                    'hospital_city' => $doctor['hospital_city'],
                    'hospital_country' => $doctor['hospital_country'],
                ];
            }, $doctors),
        ];

        echo json_encode($payload);
    }
}
