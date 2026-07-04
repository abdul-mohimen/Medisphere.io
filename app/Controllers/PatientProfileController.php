<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Database;
use App\Core\Security;
use App\Core\View;
use App\Models\Patient;
use App\Models\PatientAllergy;
use App\Models\PatientEmergencyContact;
use App\Models\PatientFamilyHistory;
use App\Models\PatientHealthMetric;
use App\Models\PatientHistoryEvent;
use App\Models\PatientInsuranceProfile;
use App\Models\PatientMedication;
use App\Models\PatientProfileShare;
use App\Models\User;

class PatientProfileController
{
    public function updateProfile(): void
    {
        $patientId = $this->requirePatient();
        if (!$patientId || !$this->checkCsrf()) {
            return;
        }

        $name = Security::cleanString($_POST['name'] ?? '');
        $email = Security::cleanEmail($_POST['email'] ?? '');
        $ageInput = trim((string) ($_POST['age'] ?? ''));
        $age = $ageInput === '' ? null : Security::cleanInt($ageInput);
        $gender = Security::cleanString($_POST['gender'] ?? '');
        $bloodGroup = Security::cleanString($_POST['blood_group'] ?? '');
        $phone = Security::cleanString($_POST['phone'] ?? '');
        $city = Security::cleanString($_POST['city'] ?? '');
        $country = Security::cleanString($_POST['country'] ?? '');
        $address = Security::cleanString($_POST['address'] ?? '');
        $removeProfileImage = !empty($_POST['remove_profile_image']);

        $errors = [];
        if ($name === '') {
            $errors[] = 'Full name is required.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email address is required.';
        }
        if ($email !== '' && strlen($email) > 190) {
            $errors[] = 'Email address must be 190 characters or fewer.';
        }
        if ($age !== null && ($age < 0 || $age > 120)) {
            $errors[] = 'Age must be between 0 and 120.';
        }
        if ($name !== '' && strlen($name) > 120) {
            $errors[] = 'Full name must be 120 characters or fewer.';
        }
        if ($phone !== '' && strlen($phone) > 30) {
            $errors[] = 'Phone number must be 30 characters or fewer.';
        }
        if ($city !== '' && strlen($city) > 100) {
            $errors[] = 'City must be 100 characters or fewer.';
        }
        if ($country !== '' && strlen($country) > 100) {
            $errors[] = 'Country must be 100 characters or fewer.';
        }
        if ($address !== '' && strlen($address) > 255) {
            $errors[] = 'Address must be 255 characters or fewer.';
        }

        $allowedGenders = ['', 'Female', 'Male', 'Non-binary', 'Prefer not to say', 'Other'];
        if (!in_array($gender, $allowedGenders, true)) {
            $errors[] = 'Please choose a valid gender option.';
        }

        $allowedBloodGroups = ['', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
        if (!in_array($bloodGroup, $allowedBloodGroups, true)) {
            $errors[] = 'Please choose a valid blood group.';
        }

        $userModel = new User();
        $existingUser = $userModel->findByEmail($email);
        if ($existingUser && (int) $existingUser['id'] !== $patientId) {
            $errors[] = 'That email address is already used by another account.';
        }

        $profileImage = null;
        try {
            $profileImage = $this->profileImageUpload($_FILES['profile_image'] ?? null);
        } catch (\RuntimeException $exception) {
            $errors[] = $exception->getMessage();
        }

        if ($errors) {
            flash('error', implode(' ', $errors), 'danger');
            redirect('profile');
        }

        $patientModel = new Patient();
        if ($profileImage || $removeProfileImage) {
            $patientModel->ensureProfileImageColumns();
        }

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $userModel->updateEmail($patientId, $email);
            $patientModel->updateProfile($patientId, [
                'name' => $name,
                'age' => $age,
                'gender' => $gender,
                'blood_group' => $bloodGroup,
                'address' => $address,
                'phone' => $phone,
                'city' => $city,
                'country' => $country,
            ]);

            if ($removeProfileImage) {
                $patientModel->clearProfileImage($patientId);
            } elseif ($profileImage) {
                $patientModel->updateProfileImage($patientId, $profileImage['data'], $profileImage['mime']);
            }

            $db->commit();
            flash('success', 'Profile details updated successfully.', 'success');
            redirect('profile');
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            flash('error', 'Profile update failed. ' . (config('app.debug') ? $exception->getMessage() : 'Please try again.'), 'danger');
            redirect('profile');
        }
    }

    public function deleteAccount(): void
    {
        $patientId = $this->requirePatient();
        if (!$patientId || !$this->checkCsrf()) {
            return;
        }

        $confirmPhrase = trim((string) ($_POST['confirm_delete'] ?? ''));
        $password = (string) ($_POST['current_password'] ?? '');
        $userModel = new User();
        $user = $userModel->findById($patientId);

        if ($confirmPhrase !== 'DELETE') {
            flash('error', 'Type DELETE to confirm account deletion.', 'danger');
            redirect('profile');
        }

        if (!$user || ($user['user_type'] ?? '') !== 'patient' || !password_verify($password, (string) ($user['password'] ?? ''))) {
            flash('error', 'Current password could not be verified.', 'danger');
            redirect('profile');
        }

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $userModel->deleteById($patientId);
            $db->commit();

            $_SESSION = [];
            session_regenerate_id(true);
            flash('success', 'Your patient account and connected profile data have been deleted securely.', 'success');
            redirect('login');
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            flash('error', 'Account deletion failed. ' . (config('app.debug') ? $exception->getMessage() : 'Please contact support.'), 'danger');
            redirect('profile');
        }
    }

    public function saveHistoryEvent(): void
    {
        $patientId = $this->requirePatient();
        if (!$patientId || !$this->checkCsrf()) {
            return;
        }

        $title = Security::cleanString($_POST['title'] ?? '');
        $eventDate = Security::cleanString($_POST['event_date'] ?? '');
        $category = Security::cleanString($_POST['category'] ?? 'general');
        $description = Security::cleanString($_POST['description'] ?? '');
        $allowedCategories = ['diagnosis', 'surgery', 'lab', 'medication', 'general'];
        if (!in_array($category, $allowedCategories, true)) {
            $category = 'general';
        }
        if ($title === '' || $eventDate === '') {
            flash('error', 'History title and date are required.', 'danger');
            redirect('profile');
        }
        if (strlen($title) > 180 || !$this->isValidDate($eventDate)) {
            flash('error', 'Please enter a valid history title and date.', 'danger');
            redirect('profile');
        }

        $this->persistProfileChange(static function () use ($patientId, $eventDate, $category, $title, $description): void {
            (new PatientHistoryEvent())->create([
                'patient_id' => $patientId,
                'event_date' => $eventDate,
                'category' => $category,
                'title' => $title,
                'description' => $description,
            ]);
        }, 'Medical history event saved.', 'Medical history event could not be saved.');
    }

    public function deleteHistoryEvent(): void
    {
        $patientId = $this->requirePatient();
        if (!$patientId || !$this->checkCsrf()) {
            return;
        }
        $historyId = Security::cleanInt($_POST['history_id'] ?? 0);
        if ($historyId <= 0) {
            flash('error', 'Invalid medical history item.', 'danger');
            redirect('profile');
        }

        $this->persistProfileChange(static function () use ($historyId, $patientId): void {
            (new PatientHistoryEvent())->delete($historyId, $patientId);
        }, 'Medical history event removed.', 'Medical history event could not be removed.');
    }

    public function addAllergy(): void
    {
        $patientId = $this->requirePatient();
        if (!$patientId || !$this->checkCsrf()) {
            return;
        }
        $allergen = Security::cleanString($_POST['allergen'] ?? '');
        $severity = Security::cleanString($_POST['severity'] ?? 'moderate');
        $notes = Security::cleanString($_POST['notes'] ?? '');
        if ($allergen === '') {
            flash('error', 'Allergen name is required.', 'danger');
            redirect('profile');
        }
        if (strlen($allergen) > 160 || strlen($notes) > 255 || !in_array($severity, ['mild', 'moderate', 'severe'], true)) {
            flash('error', 'Please enter valid allergy details.', 'danger');
            redirect('profile');
        }

        $this->persistProfileChange(static function () use ($patientId, $allergen, $severity, $notes): void {
            (new PatientAllergy())->create([
                'patient_id' => $patientId,
                'allergen' => $allergen,
                'severity' => $severity,
                'notes' => $notes,
            ]);
        }, 'Allergy added.', 'Allergy could not be saved.');
    }

    public function deleteAllergy(): void
    {
        $patientId = $this->requirePatient();
        if (!$patientId || !$this->checkCsrf()) {
            return;
        }
        $allergyId = Security::cleanInt($_POST['allergy_id'] ?? 0);
        if ($allergyId <= 0) {
            flash('error', 'Invalid allergy item.', 'danger');
            redirect('profile');
        }

        $this->persistProfileChange(static function () use ($allergyId, $patientId): void {
            (new PatientAllergy())->delete($allergyId, $patientId);
        }, 'Allergy removed.', 'Allergy could not be removed.');
    }

    public function addMedication(): void
    {
        $patientId = $this->requirePatient();
        if (!$patientId || !$this->checkCsrf()) {
            return;
        }
        $medicineName = Security::cleanString($_POST['medicine_name'] ?? '');
        $dosage = Security::cleanString($_POST['dosage'] ?? '');
        $frequency = Security::cleanString($_POST['frequency'] ?? '');
        $startDate = $this->optionalDate($_POST['start_date'] ?? '');
        $notes = Security::cleanString($_POST['notes'] ?? '');
        if ($medicineName === '') {
            flash('error', 'Medication name is required.', 'danger');
            redirect('profile');
        }
        if (
            strlen($medicineName) > 160
            || strlen($dosage) > 120
            || strlen($frequency) > 120
            || strlen($notes) > 255
            || $startDate === false
        ) {
            flash('error', 'Please enter valid medication details.', 'danger');
            redirect('profile');
        }

        $this->persistProfileChange(static function () use ($patientId, $medicineName, $dosage, $frequency, $startDate, $notes): void {
            (new PatientMedication())->create([
                'patient_id' => $patientId,
                'medicine_name' => $medicineName,
                'dosage' => $dosage,
                'frequency' => $frequency,
                'start_date' => $startDate,
                'notes' => $notes,
            ]);
        }, 'Medication added.', 'Medication could not be saved.');
    }

    public function deleteMedication(): void
    {
        $patientId = $this->requirePatient();
        if (!$patientId || !$this->checkCsrf()) {
            return;
        }
        $medicationId = Security::cleanInt($_POST['medication_id'] ?? 0);
        if ($medicationId <= 0) {
            flash('error', 'Invalid medication item.', 'danger');
            redirect('profile');
        }

        $this->persistProfileChange(static function () use ($medicationId, $patientId): void {
            (new PatientMedication())->delete($medicationId, $patientId);
        }, 'Medication removed.', 'Medication could not be removed.');
    }

    public function saveEmergencyContact(): void
    {
        $patientId = $this->requirePatient();
        if (!$patientId || !$this->checkCsrf()) {
            return;
        }
        $name = Security::cleanString($_POST['contact_name'] ?? '');
        $relationship = Security::cleanString($_POST['relationship'] ?? '');
        $phone = Security::cleanString($_POST['phone'] ?? '');
        $alternatePhone = Security::cleanString($_POST['alternate_phone'] ?? '');
        $address = Security::cleanString($_POST['address'] ?? '');
        if ($name === '') {
            flash('error', 'Emergency contact name is required.', 'danger');
            redirect('profile');
        }
        if (
            strlen($name) > 160
            || strlen($relationship) > 120
            || strlen($phone) > 40
            || strlen($alternatePhone) > 40
            || strlen($address) > 255
        ) {
            flash('error', 'Please enter valid emergency contact details.', 'danger');
            redirect('profile');
        }

        $this->persistProfileChange(static function () use ($patientId, $name, $relationship, $phone, $alternatePhone, $address): void {
            (new PatientEmergencyContact())->upsert($patientId, [
                'contact_name' => $name,
                'relationship' => $relationship,
                'phone' => $phone,
                'alternate_phone' => $alternatePhone,
                'address' => $address,
            ]);
        }, 'Emergency contact saved.', 'Emergency contact could not be saved.');
    }

    public function saveInsurance(): void
    {
        $patientId = $this->requirePatient();
        if (!$patientId || !$this->checkCsrf()) {
            return;
        }
        $providerName = Security::cleanString($_POST['provider_name'] ?? '');
        $policyNumber = Security::cleanString($_POST['policy_number'] ?? '');
        $planName = Security::cleanString($_POST['plan_name'] ?? '');
        $validUntil = $this->optionalDate($_POST['valid_until'] ?? '');
        $coverageNotes = Security::cleanString($_POST['coverage_notes'] ?? '');

        if (
            strlen($providerName) > 180
            || strlen($policyNumber) > 120
            || strlen($planName) > 160
            || $validUntil === false
        ) {
            flash('error', 'Please enter valid insurance details.', 'danger');
            redirect('profile');
        }

        $this->persistProfileChange(static function () use ($patientId, $providerName, $policyNumber, $planName, $validUntil, $coverageNotes): void {
            (new PatientInsuranceProfile())->upsert($patientId, [
                'provider_name' => $providerName,
                'policy_number' => $policyNumber,
                'plan_name' => $planName,
                'valid_until' => $validUntil,
                'coverage_notes' => $coverageNotes,
            ]);
        }, 'Insurance profile saved.', 'Insurance profile could not be saved.');
    }

    public function addFamilyHistory(): void
    {
        $patientId = $this->requirePatient();
        if (!$patientId || !$this->checkCsrf()) {
            return;
        }
        $relation = Security::cleanString($_POST['relation_name'] ?? '');
        $condition = Security::cleanString($_POST['condition_name'] ?? '');
        $notes = Security::cleanString($_POST['notes'] ?? '');
        if ($relation === '' || $condition === '') {
            flash('error', 'Relation and condition are required.', 'danger');
            redirect('profile');
        }
        if (strlen($relation) > 120 || strlen($condition) > 160 || strlen($notes) > 255) {
            flash('error', 'Please enter valid family history details.', 'danger');
            redirect('profile');
        }

        $this->persistProfileChange(static function () use ($patientId, $relation, $condition, $notes): void {
            (new PatientFamilyHistory())->create([
                'patient_id' => $patientId,
                'relation_name' => $relation,
                'condition_name' => $condition,
                'notes' => $notes,
            ]);
        }, 'Family history record added.', 'Family history could not be saved.');
    }

    public function deleteFamilyHistory(): void
    {
        $patientId = $this->requirePatient();
        if (!$patientId || !$this->checkCsrf()) {
            return;
        }
        $familyHistoryId = Security::cleanInt($_POST['family_history_id'] ?? 0);
        if ($familyHistoryId <= 0) {
            flash('error', 'Invalid family history item.', 'danger');
            redirect('profile');
        }

        $this->persistProfileChange(static function () use ($familyHistoryId, $patientId): void {
            (new PatientFamilyHistory())->delete($familyHistoryId, $patientId);
        }, 'Family history record removed.', 'Family history could not be removed.');
    }

    public function addMetric(): void
    {
        $patientId = $this->requirePatient();
        if (!$patientId || !$this->checkCsrf()) {
            return;
        }
        $metricType = Security::cleanString($_POST['metric_type'] ?? '');
        $primaryInput = trim((string) ($_POST['value_primary'] ?? ''));
        $secondaryInput = trim((string) ($_POST['value_secondary'] ?? ''));
        $unit = Security::cleanString($_POST['unit'] ?? '');
        $recordedAt = Security::cleanString($_POST['recorded_at'] ?? date('Y-m-d'));
        $notes = Security::cleanString($_POST['notes'] ?? '');
        $allowedMetricTypes = ['weight', 'blood_pressure', 'sugar', 'oxygen', 'temperature', 'heart_rate'];
        if ($metricType === '' || $primaryInput === '') {
            flash('error', 'Metric type and primary value are required.', 'danger');
            redirect('profile');
        }
        if (
            !in_array($metricType, $allowedMetricTypes, true)
            || !is_numeric($primaryInput)
            || ($secondaryInput !== '' && !is_numeric($secondaryInput))
            || strlen($unit) > 40
            || strlen($notes) > 255
            || !$this->isValidDate($recordedAt)
        ) {
            flash('error', 'Please enter valid health metric details.', 'danger');
            redirect('profile');
        }

        $primary = (float) $primaryInput;
        $secondary = $secondaryInput !== '' ? (float) $secondaryInput : null;
        $this->persistProfileChange(static function () use ($patientId, $metricType, $primary, $secondary, $unit, $recordedAt, $notes): void {
            (new PatientHealthMetric())->create([
                'patient_id' => $patientId,
                'metric_type' => $metricType,
                'value_primary' => $primary,
                'value_secondary' => $secondary,
                'unit' => $unit,
                'recorded_at' => $recordedAt,
                'notes' => $notes,
            ]);
        }, 'Health metric saved.', 'Health metric could not be saved.');
    }

    public function deleteMetric(): void
    {
        $patientId = $this->requirePatient();
        if (!$patientId || !$this->checkCsrf()) {
            return;
        }
        $metricId = Security::cleanInt($_POST['metric_id'] ?? 0);
        if ($metricId <= 0) {
            flash('error', 'Invalid health metric item.', 'danger');
            redirect('profile');
        }

        $this->persistProfileChange(static function () use ($metricId, $patientId): void {
            (new PatientHealthMetric())->delete($metricId, $patientId);
        }, 'Health metric removed.', 'Health metric could not be removed.');
    }

    public function regenerateShare(): void
    {
        $patientId = $this->requirePatient();
        if (!$patientId || !$this->checkCsrf()) {
            return;
        }
        $this->persistProfileChange(static function () use ($patientId): void {
            (new PatientProfileShare())->regenerate($patientId);
        }, 'Profile share token regenerated.', 'Profile share token could not be regenerated.');
    }

    public function publicProfile(): void
    {
        $token = Security::cleanString($_GET['token'] ?? '');
        $share = (new PatientProfileShare())->getByToken($token);
        if (!$share) {
            http_response_code(404);
            View::render('error404', ['title' => 'Profile Not Found']);
            return;
        }

        $patientId = (int) $share['patient_id'];
        View::render('patient_public_profile', [
            'title' => 'Quick Patient Profile',
            'profile' => (new Patient())->findByUserId($patientId),
            'allergies' => (new PatientAllergy())->byPatient($patientId),
            'medications' => (new PatientMedication())->byPatient($patientId),
            'emergencyContact' => (new PatientEmergencyContact())->findByPatient($patientId),
            'insurance' => (new PatientInsuranceProfile())->findByPatient($patientId),
            'familyHistory' => (new PatientFamilyHistory())->byPatient($patientId),
            'metrics' => (new PatientHealthMetric())->byPatient($patientId, 20),
            'historyEvents' => (new PatientHistoryEvent())->byPatient($patientId),
            'publicView' => true,
        ]);
    }

    private function requirePatient(): ?int
    {
        Auth::requireLogin(['patient']);
        return Auth::id();
    }

    private function checkCsrf(): bool
    {
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            flash('error', 'Invalid security token.', 'danger');
            redirect('profile');
            return false;
        }
        return true;
    }

    private function persistProfileChange(callable $operation, string $successMessage, string $failureMessage): void
    {
        try {
            $operation();
            flash('success', $successMessage, 'success');
        } catch (\Throwable $exception) {
            flash('error', $failureMessage . ' ' . (config('app.debug') ? $exception->getMessage() : 'Please try again.'), 'danger');
        }

        redirect('profile');
    }

    private function optionalDate(?string $value)
    {
        $date = Security::cleanString($value ?? '');
        if ($date === '') {
            return null;
        }

        return $this->isValidDate($date) ? $date : false;
    }

    private function isValidDate(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);
        return $date instanceof \DateTimeImmutable && $date->format('Y-m-d') === $value;
    }

    private function profileImageUpload(?array $file): ?array
    {
        if (!$file) {
            return null;
        }

        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($error !== UPLOAD_ERR_OK) {
            throw new \RuntimeException($this->uploadErrorMessage($error));
        }

        $maxBytes = 3 * 1024 * 1024;
        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0) {
            throw new \RuntimeException('Please choose a valid profile image.');
        }
        if ($size > $maxBytes) {
            throw new \RuntimeException('Profile image must be 3 MB or smaller.');
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            throw new \RuntimeException('Profile image upload could not be processed.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = (string) ($finfo->file($tmpName) ?: '');
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mimeType, $allowedMimeTypes, true)) {
            throw new \RuntimeException('Profile image must be a JPG, PNG, or WebP file.');
        }
        if (!is_array(@getimagesize($tmpName))) {
            throw new \RuntimeException('Profile image file could not be verified as an image.');
        }

        $imageData = file_get_contents($tmpName);
        if ($imageData === false || $imageData === '') {
            throw new \RuntimeException('Profile image upload could not be read.');
        }

        return [
            'data' => $imageData,
            'mime' => $mimeType,
        ];
    }

    private function uploadErrorMessage(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Profile image is larger than the server allows.',
            UPLOAD_ERR_PARTIAL => 'Profile image upload was interrupted. Please try again.',
            default => 'Profile image upload failed. Please try again.',
        };
    }
}
