<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\HtmlSanitizer;
use App\Core\Security;
use App\Core\View;
use App\Models\ComplianceIncident;
use App\Models\DataPrivacyRequest;
use App\Models\PolicyDocument;
use App\Models\User;
use App\Models\UserPolicyAcknowledgement;
use App\Services\NotificationService;

class ComplianceController
{
    public function policies(): void
    {
        View::render('policy_index', [
            'title' => 'Privacy, Terms & Compliance',
            'policies' => (new PolicyDocument())->publicAll(),
            'metaDescription' => 'Review privacy policies, terms of service, HIPAA notices, GDPR rights, and compliance statements for MediSphere.',
            'breadcrumbs' => [
                ['label' => 'Home', 'url' => route_url('home')],
                ['label' => 'Policies', 'url' => null],
            ],
        ]);
    }

    public function policy(): void
    {
        $slug = Security::cleanString($_GET['slug'] ?? '');
        $policy = (new PolicyDocument())->findBySlug($slug);
        if (!$policy || ($policy['status'] ?? '') !== 'published' || (int) ($policy['is_public'] ?? 0) !== 1) {
            http_response_code(404);
            View::render('error404', ['title' => 'Policy Not Found']);
            return;
        }

        View::render('policy_view', [
            'title' => $policy['title'],
            'policy' => $policy,
            'metaDescription' => substr(strip_tags((string) $policy['content']), 0, 160),
            'canonicalUrl' => route_url('policy', ['slug' => $policy['slug']]),
            'breadcrumbs' => [
                ['label' => 'Home', 'url' => route_url('home')],
                ['label' => 'Policies', 'url' => route_url('policies')],
                ['label' => $policy['title'], 'url' => null],
            ],
        ]);
    }

    public function center(): void
    {
        Auth::requireLogin();
        $policyModel = new PolicyDocument();
        $requestModel = new DataPrivacyRequest();
        $ackModel = new UserPolicyAcknowledgement();

        View::render('compliance_center', [
            'title' => 'Compliance Center',
            'breadcrumbs' => [
                ['label' => 'Home', 'url' => route_url('dashboard')],
                ['label' => 'Compliance Center', 'url' => null],
            ],
            'pendingPolicies' => $policyModel->pendingAcknowledgementsForUser(Auth::id()),
            'acknowledgements' => $ackModel->byUser(Auth::id()),
            'requests' => $requestModel->byUser(Auth::id()),
            'publicPolicies' => $policyModel->publicAll(),
        ]);
    }

    public function acknowledge(): void
    {
        Auth::requireLogin();
        if (!$this->checkCsrf('compliance-center')) {
            return;
        }

        $policyId = Security::cleanInt($_POST['policy_id'] ?? 0);
        $policy = (new PolicyDocument())->find($policyId);
        if (!$policy || ($policy['status'] ?? '') !== 'published') {
            flash('error', 'Policy document not found.', 'danger');
            redirect('compliance-center');
        }

        (new UserPolicyAcknowledgement())->acknowledge($policyId, Auth::id());
        flash('success', 'Policy acknowledgement saved.', 'success');
        redirect('compliance-center');
    }

    public function submitRequest(): void
    {
        Auth::requireLogin();
        if (!$this->checkCsrf('compliance-center')) {
            return;
        }

        $requestType = Security::cleanString($_POST['request_type'] ?? '');
        $allowed = ['access', 'export', 'delete', 'correction', 'restriction'];
        if (!in_array($requestType, $allowed, true)) {
            flash('error', 'Invalid privacy request type.', 'danger');
            redirect('compliance-center');
        }

        $description = Security::cleanString($_POST['description'] ?? '');
        if ($description === '') {
            flash('error', 'Please describe your request.', 'danger');
            redirect('compliance-center');
        }

        $requestId = (new DataPrivacyRequest())->create([
            'user_id' => Auth::id(),
            'request_type' => $requestType,
            'description' => $description,
            'status' => 'submitted',
        ]);

        foreach ((new User())->all('admin') as $admin) {
            (new NotificationService())->sendToUser((int) $admin['id'], 'generic', [
                'name' => $admin['email'],
                'platform_name' => config('app.name', 'MediSphere'),
            ], [
                'category' => 'security',
                'type' => 'warning',
                'action_url' => route_url('admin/compliance'),
            ]);
        }

        flash('success', 'Privacy request submitted successfully. Reference #' . $requestId, 'success');
        redirect('compliance-center');
    }

    public function admin(): void
    {
        Auth::requireLogin(['admin']);
        $policyModel = new PolicyDocument();
        $requestModel = new DataPrivacyRequest();
        $incidentModel = new ComplianceIncident();
        $ackModel = new UserPolicyAcknowledgement();

        $policies = $policyModel->all();
        $policyAcks = [];
        foreach ($policies as $policy) {
            $policyAcks[(int) $policy['id']] = $ackModel->countByPolicy((int) $policy['id']);
        }

        View::render('admin_compliance', [
            'title' => 'Compliance Center',
            'breadcrumbs' => [
                ['label' => 'Home', 'url' => route_url('dashboard')],
                ['label' => 'Compliance Operations', 'url' => null],
            ],
            'policies' => $policies,
            'policyAcks' => $policyAcks,
            'requests' => $requestModel->all(),
            'incidents' => $incidentModel->all(),
            'stats' => [
                'pending_requests' => $requestModel->pendingCount(),
                'open_incidents' => $incidentModel->openCount(),
                'published_policies' => count(array_filter($policies, fn(array $policy) => ($policy['status'] ?? '') === 'published')),
            ],
        ]);
    }

    public function savePolicy(): void
    {
        Auth::requireLogin(['admin']);
        if (!$this->checkCsrf('admin/compliance')) {
            return;
        }

        $title = Security::cleanString($_POST['title'] ?? '');
        if ($title === '') {
            flash('error', 'Policy title is required.', 'danger');
            redirect('admin/compliance');
        }

        $slug = $this->slugify(Security::cleanString($_POST['slug'] ?? '') ?: $title);
        (new PolicyDocument())->save([
            'id' => Security::cleanInt($_POST['policy_id'] ?? 0),
            'slug' => $slug,
            'title' => $title,
            'category' => Security::cleanString($_POST['category'] ?? 'privacy_policy'),
            'content' => HtmlSanitizer::clean(trim((string) ($_POST['content'] ?? ''))),
            'status' => Security::cleanString($_POST['status'] ?? 'published'),
            'version_label' => Security::cleanString($_POST['version_label'] ?? 'v1.0'),
            'effective_date' => Security::cleanString($_POST['effective_date'] ?? date('Y-m-d')),
            'requires_acknowledgement' => isset($_POST['requires_acknowledgement']) ? 1 : 0,
            'is_public' => isset($_POST['is_public']) ? 1 : 0,
        ]);

        flash('success', 'Policy document saved successfully.', 'success');
        redirect('admin/compliance');
    }

    public function updateRequest(): void
    {
        Auth::requireLogin(['admin']);
        if (!$this->checkCsrf('admin/compliance')) {
            return;
        }

        $id = Security::cleanInt($_POST['request_id'] ?? 0);
        $status = Security::cleanString($_POST['status'] ?? 'submitted');
        $allowed = ['submitted', 'in_review', 'completed', 'rejected'];
        if (!in_array($status, $allowed, true)) {
            flash('error', 'Invalid request status.', 'danger');
            redirect('admin/compliance');
        }

        $request = (new DataPrivacyRequest())->find($id);
        if (!$request) {
            flash('error', 'Privacy request not found.', 'danger');
            redirect('admin/compliance');
        }

        (new DataPrivacyRequest())->updateStatus($id, [
            'status' => $status,
            'admin_notes' => Security::cleanString($_POST['admin_notes'] ?? ''),
            'resolved_by' => Auth::id(),
            'resolved_at' => in_array($status, ['completed', 'rejected'], true) ? date('Y-m-d H:i:s') : null,
        ]);

        (new NotificationService())->sendToUser((int) $request['user_id'], 'generic', [
            'name' => (new User())->contactProfile((int) $request['user_id'])['name'] ?? 'User',
            'platform_name' => config('app.name', 'MediSphere'),
        ], [
            'category' => 'security',
            'type' => in_array($status, ['completed'], true) ? 'success' : 'info',
            'action_url' => route_url('compliance-center'),
        ]);

        flash('success', 'Privacy request updated.', 'success');
        redirect('admin/compliance');
    }

    public function saveIncident(): void
    {
        Auth::requireLogin(['admin']);
        if (!$this->checkCsrf('admin/compliance')) {
            return;
        }

        $title = Security::cleanString($_POST['title'] ?? '');
        if ($title === '') {
            flash('error', 'Incident title is required.', 'danger');
            redirect('admin/compliance');
        }

        (new ComplianceIncident())->create([
            'title' => $title,
            'incident_type' => Security::cleanString($_POST['incident_type'] ?? 'policy'),
            'severity' => Security::cleanString($_POST['severity'] ?? 'medium'),
            'status' => Security::cleanString($_POST['status'] ?? 'open'),
            'description' => Security::cleanString($_POST['description'] ?? ''),
            'action_taken' => Security::cleanString($_POST['action_taken'] ?? ''),
            'reported_by' => Auth::id(),
            'resolved_by' => in_array(Security::cleanString($_POST['status'] ?? 'open'), ['resolved', 'closed'], true) ? Auth::id() : null,
        ]);

        flash('success', 'Compliance incident logged successfully.', 'success');
        redirect('admin/compliance');
    }

    private function checkCsrf(string $fallbackRoute): bool
    {
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            flash('error', 'Invalid security token.', 'danger');
            redirect($fallbackRoute);
            return false;
        }
        return true;
    }

    private function slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/i', '-', $value) ?: 'policy';
        return trim($value, '-');
    }
}
