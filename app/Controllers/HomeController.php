<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\View;
use App\Models\CmsArticle;
use App\Models\CmsFaq;
use App\Models\Doctor;
use App\Models\HealthArticleCatalog;
use App\Models\Hospital;
use App\Models\SiteSetting;
use App\Services\NewsletterSignup;
use App\Services\SubscriptionAccess;

class HomeController
{
    public function index(): void
    {
        $featuredDoctors = array_slice((new Doctor())->search(['active_only' => true]), 0, 6);
        $hospitals = array_slice((new Hospital())->all(), 0, 6);
        $blogPosts = array_slice((new HealthArticleCatalog())->all(), 0, 3);
        $settings = new SiteSetting();

        View::render('home', [
            'title' => __('home.title'),
            'metaDescription' => __('home.subtitle'),
            'featuredDoctors' => $featuredDoctors,
            'featuredHospitals' => $hospitals,
            'blogPosts' => $blogPosts,
            'homepageNotice' => $settings->get('homepage_notice', ''),
            'aboutSummary' => $settings->get('about_summary', ''),
            'footerContact' => $settings->get('footer_contact', ''),
            'emergencyHotline' => $settings->get('emergency_hotline', ''),
        ]);
    }

    public function subscribeNewsletter(): void
    {
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            flash('error', 'Invalid security token. Please try again.', 'danger');
            header('Location: ' . route_url('home') . '#newsletter');
            exit;
        }

        $email = trim((string) ($_POST['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Please enter a valid email address.', 'danger');
            header('Location: ' . route_url('home') . '#newsletter');
            exit;
        }

        (new NewsletterSignup())->rememberPending($email);
        flash('success', 'Choose a subscription package to complete newsletter access. Paid plans unlock premium updates; Free keeps premium updates locked.', 'info');
        header('Location: ' . route_url('payments/subscriptions', [
            'source' => 'newsletter',
            'return_to' => route_url('home') . '#newsletter',
        ]));
        exit;
    }

    public function faq(): void
    {
        $faqModel = new CmsFaq();
        View::render('faq', [
            'title' => __('faq.title'),
            'metaDescription' => __('faq.subtitle'),
            'breadcrumbs' => [['label' => 'Home', 'url' => route_url('home')], ['label' => __('faq.title'), 'url' => null]],
            'faqGroups' => $faqModel->groupedPublished(),
        ]);
    }

    public function guidelines(): void
    {
        $articleModel = new CmsArticle();
        View::render('guidelines', [
            'title' => __('guidelines.title'),
            'metaDescription' => 'Read platform usage guidance, emergency protocols, and help documentation on MediSphere.',
            'breadcrumbs' => [['label' => 'Home', 'url' => route_url('home')], ['label' => __('guidelines.title'), 'url' => null]],
            'guidelines' => $articleModel->byType('guideline'),
            'protocols' => $articleModel->byType('emergency_protocol'),
        ]);
    }

    public function map(): void
    {
        $subscriptionAccess = new SubscriptionAccess();
        $subscriptionLocked = !$subscriptionAccess->canAccessLevel('premium', Auth::check() && Auth::type() === 'patient' ? Auth::id() : null);

        $hospitals = (new Hospital())->all();
        $maxFee = trim((string) ($_GET['max_fee'] ?? ''));
        if ($maxFee !== '' && (!is_numeric($maxFee) || (float) $maxFee < 0)) {
            $maxFee = '';
        }

        $hospitalId = trim((string) ($_GET['hospital_id'] ?? ''));
        if ($hospitalId !== '' && (!ctype_digit($hospitalId) || (int) $hospitalId < 1)) {
            $hospitalId = '';
        }

        $doctorFilters = [
            'active_only' => true,
            'specialization' => trim((string) ($_GET['specialization'] ?? '')),
            'location' => trim((string) ($_GET['location'] ?? '')),
            'hospital_id' => $hospitalId,
            'max_fee' => $maxFee,
        ];
        $doctors = (new Doctor())->search($doctorFilters);
        View::render('map', [
            'title' => __('common.map'),
            'metaDescription' => 'Explore hospitals, doctors, pharmacies, and emergency facilities with the MediSphere healthcare map.',
            'breadcrumbs' => [['label' => 'Home', 'url' => route_url('home')], ['label' => __('common.map'), 'url' => null]],
            'hospitals' => $hospitals,
            'doctors' => $doctors,
            'subscriptionLocked' => $subscriptionLocked,
            ...$subscriptionAccess->modalData(
                'Premium healthcare map tools',
                'Subscribe to use live location routing, satellite view, directions, and advanced nearby-care discovery.',
                route_url('map'),
                false
            ),
        ]);
    }
}
