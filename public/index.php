<?php
require __DIR__ . '/../app/bootstrap.php';

use App\Controllers\AdminController;
use App\Controllers\AdminContentController;
use App\Controllers\AppointmentController;
use App\Controllers\ConsultationController;
use App\Controllers\ContentController;
use App\Controllers\ClinicalController;
use App\Controllers\ComplianceController;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\HealthcareController;
use App\Controllers\HospitalManagementController;
use App\Controllers\LocalizationController;
use App\Controllers\ChatbotController;
use App\Controllers\HomeController;
use App\Controllers\MessageController;
use App\Controllers\NotificationController;
use App\Controllers\PatientProfileController;
use App\Controllers\PaymentController;
use App\Controllers\RecordConsentController;
use App\Controllers\ReportController;
use App\Controllers\SearchController;
use App\Controllers\ScannerController;
use App\Controllers\SystemController;
use App\Controllers\SetupController;
use App\Core\View;

$route = $_GET['route'] ?? config('app.default_route', 'home');
$method = request_method();

$routes = [
    'GET' => [
        'home' => [HomeController::class, 'index'],
        'api/search-suggestions' => [SearchController::class, 'suggestions'],
        'sitemap.xml' => [SystemController::class, 'sitemap'],
        'robots.txt' => [SystemController::class, 'robots'],
        'auth' => [AuthController::class, 'show'],
        'login' => [AuthController::class, 'showLogin'],
        'auth/google' => [AuthController::class, 'googleRedirect'],
        'auth/google/callback' => [AuthController::class, 'googleCallback'],
        'auth/facebook' => [AuthController::class, 'facebookRedirect'],
        'auth/facebook/callback' => [AuthController::class, 'facebookCallback'],
        'auth/x' => [AuthController::class, 'xRedirect'],
        'auth/x/callback' => [AuthController::class, 'xCallback'],
        'auth/twitter' => [AuthController::class, 'xRedirect'],
        'auth/twitter/callback' => [AuthController::class, 'xCallback'],
        'oauth/google' => [AuthController::class, 'googleRedirect'],
        'oauth/google/callback' => [AuthController::class, 'googleCallback'],
        'oauth/facebook' => [AuthController::class, 'facebookRedirect'],
        'oauth/facebook/callback' => [AuthController::class, 'facebookCallback'],
        'oauth/x' => [AuthController::class, 'xRedirect'],
        'oauth/x/callback' => [AuthController::class, 'xCallback'],
        'oauth/twitter' => [AuthController::class, 'xRedirect'],
        'oauth/twitter/callback' => [AuthController::class, 'xCallback'],
        'register' => [AuthController::class, 'showRegister'],
        'policies' => [ComplianceController::class, 'policies'],
        'policy' => [ComplianceController::class, 'policy'],
        'blog' => [ContentController::class, 'blog'],
        'article' => [ContentController::class, 'article'],
        'diseases' => [ContentController::class, 'diseases'],
        'disease' => [ContentController::class, 'disease'],
        'reset-password' => [AuthController::class, 'showResetPassword'],
        'dashboard' => [DashboardController::class, 'index'],
        'profile' => [DashboardController::class, 'profile'],
        'appointments' => [AppointmentController::class, 'index'],
        'record-consents' => [RecordConsentController::class, 'index'],
        'compliance-center' => [ComplianceController::class, 'center'],
        'clinical-records' => [ClinicalController::class, 'index'],
        'clinical-records/prescription' => [ClinicalController::class, 'prescription'],
        'clinical-records/document' => [ClinicalController::class, 'document'],
        'verify-document' => [ClinicalController::class, 'verify'],
        'consultations' => [ConsultationController::class, 'index'],
        'consultations/room' => [ConsultationController::class, 'room'],
        'api/consultations/session' => [ConsultationController::class, 'sessionInfoApi'],
        'api/consultations/signals' => [ConsultationController::class, 'signalsApi'],
        'payments' => [PaymentController::class, 'index'],
        'payments/subscriptions' => [PaymentController::class, 'subscriptions'],
        'payments/subscription/callback' => [PaymentController::class, 'subscriptionCallback'],
        'payments/subscription/cancel' => [PaymentController::class, 'subscriptionCancel'],
        'payments/subscription/success' => [PaymentController::class, 'subscriptionSuccess'],
        'payments/subscription/invoice' => [PaymentController::class, 'subscriptionInvoice'],
        'profile/public' => [PatientProfileController::class, 'publicProfile'],
        'hospital-management' => [HospitalManagementController::class, 'index'],
        'hospital-management/patient-record' => [HospitalManagementController::class, 'patientRecord'],
        'payments/invoice' => [PaymentController::class, 'invoice'],
        'notifications' => [NotificationController::class, 'index'],
        'api/notifications' => [NotificationController::class, 'api'],
        'admin/communications' => [NotificationController::class, 'templates'],
        'admin/content' => [AdminContentController::class, 'index'],
        'admin/compliance' => [ComplianceController::class, 'admin'],
        'reports' => [ReportController::class, 'index'],
        'messages' => [MessageController::class, 'index'],
        'api/messages' => [MessageController::class, 'fetch'],
        'api/facilities' => [HealthcareController::class, 'facilitiesApi'],
        'scanner' => [ScannerController::class, 'index'],
        'find-healthcare' => [HealthcareController::class, 'find'],
        'faq' => [HomeController::class, 'faq'],
        'guidelines' => [HomeController::class, 'guidelines'],
        'map' => [HomeController::class, 'map'],
        'api/diseases' => [ContentController::class, 'diseasesApi'],
        'admin/verifications' => [AdminController::class, 'verifications'],
        'setup-admin' => [SetupController::class, 'show'],
        'logout' => [AuthController::class, 'logout'],
    ],
    'POST' => [
        'language/set' => [LocalizationController::class, 'set'],
        'api/translate-page' => [LocalizationController::class, 'translatePage'],
        'newsletter/subscribe' => [HomeController::class, 'subscribeNewsletter'],
        'login' => [AuthController::class, 'login'],
        'register' => [AuthController::class, 'register'],
        'forgot-password' => [AuthController::class, 'forgotPassword'],
        'reset-password-submit' => [AuthController::class, 'resetPassword'],
        'appointments/create' => [AppointmentController::class, 'create'],
        'record-consents/save' => [RecordConsentController::class, 'save'],
        'compliance/acknowledge' => [ComplianceController::class, 'acknowledge'],
        'compliance/request' => [ComplianceController::class, 'submitRequest'],
        'record-consents/revoke' => [RecordConsentController::class, 'revoke'],
        'clinical-records/signature/save' => [ClinicalController::class, 'saveSignatureProfile'],
        'clinical-records/prescription/save' => [ClinicalController::class, 'savePrescription'],
        'clinical-records/document/save' => [ClinicalController::class, 'saveDocument'],
        'appointments/update' => [AppointmentController::class, 'update'],
        'consultations/create' => [ConsultationController::class, 'create'],
        'consultations/availability' => [ConsultationController::class, 'availability'],
        'consultations/end' => [ConsultationController::class, 'end'],
        'consultations/feedback' => [ConsultationController::class, 'feedback'],
        'api/consultations/signal' => [ConsultationController::class, 'sendSignal'],
        'profile/update' => [PatientProfileController::class, 'updateProfile'],
        'profile/delete-account' => [PatientProfileController::class, 'deleteAccount'],
        'profile/history/save' => [PatientProfileController::class, 'saveHistoryEvent'],
        'profile/history/delete' => [PatientProfileController::class, 'deleteHistoryEvent'],
        'profile/allergy/add' => [PatientProfileController::class, 'addAllergy'],
        'profile/allergy/delete' => [PatientProfileController::class, 'deleteAllergy'],
        'profile/medication/add' => [PatientProfileController::class, 'addMedication'],
        'profile/medication/delete' => [PatientProfileController::class, 'deleteMedication'],
        'profile/emergency-contact/save' => [PatientProfileController::class, 'saveEmergencyContact'],
        'profile/insurance/save' => [PatientProfileController::class, 'saveInsurance'],
        'profile/family-history/add' => [PatientProfileController::class, 'addFamilyHistory'],
        'profile/family-history/delete' => [PatientProfileController::class, 'deleteFamilyHistory'],
        'profile/metric/add' => [PatientProfileController::class, 'addMetric'],
        'profile/metric/delete' => [PatientProfileController::class, 'deleteMetric'],
        'profile/share/regenerate' => [PatientProfileController::class, 'regenerateShare'],
        'payments/create-invoice' => [PaymentController::class, 'createInvoice'],
        'payments/subscription/free' => [PaymentController::class, 'activateFreeSubscription'],
        'payments/subscription/checkout' => [PaymentController::class, 'subscriptionCheckout'],
        'hospital-management/department/save' => [HospitalManagementController::class, 'saveDepartment'],
        'hospital-management/inventory/save' => [HospitalManagementController::class, 'saveInventory'],
        'hospital-management/bed/save' => [HospitalManagementController::class, 'saveBed'],
        'hospital-management/request/update' => [HospitalManagementController::class, 'updateRequest'],
        'hospital-management/assignment/update' => [HospitalManagementController::class, 'updateAssignment'],
        'payments/checkout' => [PaymentController::class, 'checkout'],
        'payments/webhook/verifone' => [PaymentController::class, 'verifoneWebhook'],
        'payments/webhook/payoneer' => [PaymentController::class, 'payoneerWebhook'],
        'payments/refund-request' => [PaymentController::class, 'requestRefund'],
        'notifications/mark-read' => [NotificationController::class, 'markRead'],
        'notifications/mark-all-read' => [NotificationController::class, 'markAllRead'],
        'notifications/preferences' => [NotificationController::class, 'updatePreferences'],
        'admin/content/faq-save' => [AdminContentController::class, 'saveFaq'],
        'admin/content/article-save' => [AdminContentController::class, 'saveArticle'],
        'admin/content/disease-save' => [AdminContentController::class, 'saveDisease'],
        'admin/content/settings-save' => [AdminContentController::class, 'saveSettings'],
        'admin/content/media-upload' => [AdminContentController::class, 'uploadMedia'],
        'admin/compliance/policy-save' => [ComplianceController::class, 'savePolicy'],
        'admin/compliance/request-update' => [ComplianceController::class, 'updateRequest'],
        'admin/compliance/incident-save' => [ComplianceController::class, 'saveIncident'],
        'admin/communications/email-save' => [NotificationController::class, 'saveEmailTemplate'],
        'admin/communications/sms-save' => [NotificationController::class, 'saveSmsTemplate'],
        'admin/refunds/update' => [PaymentController::class, 'updateRefund'],
        'reports/upload' => [ReportController::class, 'upload'],
        'reports/delete' => [ReportController::class, 'delete'],
        'api/send-message' => [MessageController::class, 'send'],
        'api/chatbot' => [ChatbotController::class, 'ask'],
        'scanner/analyze' => [ScannerController::class, 'analyze'],
        'scanner/save' => [ScannerController::class, 'save'],
        'admin/verify-doctor' => [AdminController::class, 'verifyDoctor'],
        'admin/verify-hospital' => [AdminController::class, 'verifyHospital'],
        'setup-admin/create' => [SetupController::class, 'create'],
    ],
];

$handler = $routes[$method][$route] ?? null;

if (!$handler) {
    http_response_code(404);
    View::render('error404', ['title' => 'Not Found']);
    exit;
}

[$controllerClass, $action] = $handler;
$controller = new $controllerClass();
$controller->$action();
