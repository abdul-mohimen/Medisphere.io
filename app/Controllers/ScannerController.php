<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Security;
use App\Core\View;
use App\Models\Appointment;
use App\Models\DiseaseScan;
use App\Models\DiseaseInformation;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Services\SubscriptionAccess;

class ScannerController
{
    private const HOURLY_ANALYSIS_LIMIT = 100;

    public function index(): void
    {
        // Allow guests to see the page so the Subscription Gate can show up instead of immediately redirecting to login.
        $subscriptionAccess = new SubscriptionAccess();
        $subscriptionLocked = $subscriptionAccess->shouldGatePatientFeature();
        $patientId = Auth::id();

        View::render('scanner', [
            'title' => 'AI Disease Scanner',
            'recentScans' => $patientId ? (new DiseaseScan())->recentByPatient($patientId) : [],
            'consultationDoctors' => $this->consultationDoctorDirectory(),
            'aiModelUrl' => config('services.ai_model_url'),
            'demoMode' => config('features.ai_demo_mode'),
            'subscriptionLocked' => $subscriptionLocked,
            ...$subscriptionAccess->modalData(
                'Premium AI disease scanner',
                'Subscribe to analyze scan images, save scan history, and unlock specialist guidance from the scanner workflow.',
                route_url('scanner'),
                false
            ),
        ]);
    }

    public function analyze(): void
    {
        Auth::requireLogin(['patient']);
        header('Content-Type: application/json');

        if ((new SubscriptionAccess())->shouldGatePatientFeature()) {
            http_response_code(402);
            echo json_encode(['success' => false, 'message' => 'Premium subscription required for AI scanner access.']);
            return;
        }

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Invalid security token']);
            return;
        }

        $mode = Security::cleanString($_POST['scan_type'] ?? 'image');
        $mode = in_array($mode, ['image', 'symptom'], true) ? $mode : 'image';
        $bodyPart = Security::cleanString($_POST['body_part'] ?? 'general');
        $bodyPart = array_key_exists($bodyPart, $this->bodyPartMap()) ? $bodyPart : 'general';
        $symptoms = $this->limitText(Security::cleanString($_POST['symptoms'] ?? ''), 1800);

        $hasIncomingImage = isset($_FILES['scan_image']) && ($_FILES['scan_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
        if ($mode === 'image') {
            $imageError = $this->validateIncomingImage($_FILES['scan_image'] ?? null, true);
            if ($imageError !== null) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => $imageError]);
                return;
            }
        } elseif ($hasIncomingImage) {
            $imageError = $this->validateIncomingImage($_FILES['scan_image'] ?? null, false);
            if ($imageError !== null) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => $imageError]);
                return;
            }
        }

        if ($mode === 'symptom' && mb_strlen($symptoms) < 4) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Please describe symptoms or the disease concern first.']);
            return;
        }

        $quota = $this->consumeAnalysisQuota(Auth::id());
        if (!$quota['allowed']) {
            http_response_code(429);
            echo json_encode([
                'success' => false,
                'message' => 'Scanner usage limit reached. Please wait before running more analyses.',
                'usage_remaining' => 0,
            ]);
            return;
        }

        $fileName = '';
        $relativePath = null;
        if ($hasIncomingImage) {
            $fileName = isset($_FILES['scan_image']['name']) ? (string) $_FILES['scan_image']['name'] : '';
            $relativePath = $this->storeScanImage($_FILES['scan_image']);
        }
        $analysis = $this->buildAnalysis($mode, $bodyPart, $symptoms, $fileName, $relativePath);
        echo json_encode([
            'success' => true,
            'analysis' => $analysis,
            'usage_remaining' => $quota['remaining'],
        ]);
    }

    public function save(): void
    {
        Auth::requireLogin(['patient']);
        header('Content-Type: application/json');

        if ((new SubscriptionAccess())->shouldGatePatientFeature()) {
            http_response_code(402);
            echo json_encode(['success' => false, 'message' => 'Premium subscription required for AI scanner access.']);
            return;
        }

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Invalid security token']);
            return;
        }

        $mode = Security::cleanString($_POST['scan_type'] ?? 'image');
        $mode = in_array($mode, ['image', 'symptom'], true) ? $mode : 'image';
        $bodyPart = Security::cleanString($_POST['body_part'] ?? 'general');
        $bodyPart = array_key_exists($bodyPart, $this->bodyPartMap()) ? $bodyPart : 'general';
        $symptoms = $this->limitText(Security::cleanString($_POST['symptoms'] ?? ''), 1800);
        $relativePath = '';

        $hasImage = isset($_FILES['scan_image']) && ($_FILES['scan_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
        if ($mode === 'image' && !$hasImage) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Scan image is required']);
            return;
        }

        if ($hasImage) {
            $imageError = $this->validateIncomingImage($_FILES['scan_image'], true);
            if ($imageError !== null) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => $imageError]);
                return;
            }

            $relativePath = $this->storeScanImage($_FILES['scan_image']);
            if ($relativePath === null) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Could not save scan image']);
                return;
            }
        }

        $id = (new DiseaseScan())->create([
            'patient_id' => Auth::id(),
            'scan_image' => $relativePath,
            'ai_result' => $this->limitText(Security::cleanString($_POST['ai_result'] ?? 'Analysis pending'), 240),
            'confidence_score' => (float) ($_POST['confidence_score'] ?? 0),
            'scan_type' => $mode,
            'body_part' => $bodyPart,
            'symptom_text' => $symptoms,
            'urgency_level' => Security::cleanString($_POST['urgency_level'] ?? 'routine'),
            'specialist_recommendation' => $this->limitText(Security::cleanString($_POST['specialist_recommendation'] ?? ''), 190),
            'care_recommendations' => $this->normalizeJsonPayload((string) ($_POST['care_recommendations'] ?? '')),
        ]);

        echo json_encode(['success' => true, 'id' => $id, 'path' => $relativePath]);
    }

    private function validateIncomingImage(?array $file, bool $required): ?string
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return $required ? 'Scan image is required' : null;
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return 'Image upload failed. Please choose the file again.';
        }

        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        $mime = mime_content_type($file['tmp_name']);
        $maxBytes = (int) config('app.upload_max_mb', 10) * 1024 * 1024;
        if (!in_array($mime, $allowed, true) || (int) ($file['size'] ?? 0) > $maxBytes) {
            return 'Only JPG, PNG, or WebP images under the upload limit are allowed';
        }

        return null;
    }

    private function storeScanImage(array $file): ?string
    {
        $mime = mime_content_type($file['tmp_name']);
        $extension = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION)),
        };

        $uploadDir = __DIR__ . '/../../public/uploads/scans';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
            return null;
        }

        $filename = bin2hex(random_bytes(12)) . '.' . $extension;
        $relativePath = 'uploads/scans/' . $filename;
        $target = __DIR__ . '/../../public/' . $relativePath;
        return move_uploaded_file($file['tmp_name'], $target) ? $relativePath : null;
    }

    private function buildAnalysis(string $mode, string $bodyPart, string $symptoms, string $fileName, string $imagePath = null): array
    {
        $bodyMap = $this->bodyPartMap();
        $body = $bodyMap[$bodyPart] ?? $bodyMap['general'];
        $context = strtolower(trim($symptoms . ' ' . str_replace(['_', '-'], ' ', $bodyPart) . ' ' . $fileName));
        
        // Prepare Gemini API Prompt
        $prompt = "You are a professional medical AI assistant. Analyze the following patient case and return a strict JSON object with this exact structure: {\"title\": \"Short diagnosis title\", \"summary\": \"Detailed explanation of potential issues\", \"confidence_score\": integer between 50 and 95, \"urgency_level\": \"routine\", \"soon\", or \"urgent\", \"possible_conditions\": [{\"name\": \"Condition name\", \"summary\": \"Condition summary\", \"match_score\": integer}], \"specialist_recommendation\": \"Specialty name\"}. Patient symptoms: \"$symptoms\". Body part affected: \"$bodyPart\".";
        
        $geminiResponse = $this->callGeminiAPI($prompt, $imagePath);
        
        if ($geminiResponse) {
            $primary = [
                'name' => $geminiResponse['title'] ?? 'General health concern',
                'summary' => $geminiResponse['summary'] ?? 'The information provided needs a clinician review.',
                'specialists' => array_merge([$geminiResponse['specialist_recommendation'] ?? 'Family Medicine'], $body['specialists']),
                'library_query' => $body['library_query'],
                'score' => 2,
            ];
            $urgency = $geminiResponse['urgency_level'] ?? 'routine';
            $confidence = (int) ($geminiResponse['confidence_score'] ?? 75);
            $possibleConditions = $geminiResponse['possible_conditions'] ?? [];
        } else {
            // Fallback if API fails or is not configured
            $conditionMatches = $this->conditionMatches($context, $bodyPart);
            $primary = $conditionMatches[0] ?? [
                'name' => 'General health concern',
                'summary' => 'The information provided needs a clinician review with history, examination, and basic tests.',
                'specialists' => $body['specialists'],
                'library_query' => $body['library_query'],
                'score' => 1,
            ];
            $urgentSignals = $this->urgentSignals($context);
            $urgency = $urgentSignals ? 'urgent' : ($this->containsAny($context, ['worse', 'severe', 'persistent', 'spreading', 'swelling', 'blood', 'vomit', 'high fever']) ? 'soon' : 'routine');
            $confidence = min(88, max(54, 58 + ((int) $primary['score'] * 7) + ($mode === 'image' ? 3 : 0)));
            $possibleConditions = array_slice(array_map(function (array $item): array {
                return [
                    'name' => $item['name'],
                    'summary' => $item['summary'],
                    'match_score' => min(96, 48 + ((int) $item['score'] * 11)),
                ];
            }, $conditionMatches), 0, 4);
        }

        $specialists = array_values(array_unique($primary['specialists']));
        $library = $this->libraryMatches([], $body['library_query'], $symptoms);
        $providers = $this->providerRecommendations($specialists, $bodyPart);

        return [
            'scan_type' => $mode,
            'body_part' => $bodyPart,
            'body_label' => $body['label'],
            'title' => $primary['name'],
            'summary' => $this->summaryFor($mode, $primary, $body['label'], $urgency),
            'confidence_score' => $confidence,
            'urgency_level' => $urgency,
            'specialist_recommendation' => implode(' / ', array_slice($specialists, 0, 3)),
            'possible_conditions' => $possibleConditions,
            'disease_library' => $library,
            'red_flags' => $body['red_flags'],
            'next_steps' => $this->nextSteps($mode, $urgency, $specialists),
            'doctor_questions' => [
                'When did symptoms start, and are they improving or getting worse?',
                'Do you have fever, severe pain, breathing trouble, bleeding, weakness, or fainting?',
                'Which medicines, allergies, reports, or previous conditions should the doctor know?',
            ],
            'doctors' => $providers['doctors'],
            'hospitals' => $providers['hospitals'],
            'disclaimer' => 'Important: This is an AI assessment. Please consult a concerned professional medical doctor for actual diagnosis.',
            'generated_at' => date('Y-m-d H:i:s'),
        ];
    }

    private function callGeminiAPI(string $prompt, ?string $imagePath): ?array
    {
        $apiKey = config('services.gemini_api_key') ?? '';
        if (empty($apiKey)) {
            return null;
        }

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.1-pro-high:generateContent?key=' . $apiKey;
        $contents = [['parts' => [['text' => $prompt]]]];

        if ($imagePath && file_exists(__DIR__ . '/../../public/' . $imagePath)) {
            $imageData = base64_encode(file_get_contents(__DIR__ . '/../../public/' . $imagePath));
            $mime = mime_content_type(__DIR__ . '/../../public/' . $imagePath);
            $contents[0]['parts'][] = [
                'inline_data' => [
                    'mime_type' => $mime,
                    'data' => $imageData
                ]
            ];
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['contents' => $contents]));
        $response = curl_exec($ch);
        curl_close($ch);

        if ($response) {
            $data = json_decode($response, true);
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
            // Extract JSON from response
            if (preg_match('/```json(.*?)```/s', $text, $matches)) {
                $text = $matches[1];
            }
            return json_decode(trim($text), true);
        }
        return null;
    }

    private function bodyPartMap(): array
    {
        return [
            'general' => [
                'label' => 'General body',
                'specialists' => ['Family Medicine', 'Internal Medicine'],
                'library_query' => 'fever',
                'red_flags' => ['Chest pain, breathing trouble, fainting, confusion, severe dehydration, or rapidly worsening symptoms need urgent care.'],
                'hospital_needles' => ['Emergency', 'Lab', 'Radiology'],
            ],
            'skin' => [
                'label' => 'Skin',
                'specialists' => ['Dermatology', 'Family Medicine'],
                'library_query' => 'eczema',
                'red_flags' => ['Rapidly spreading redness, fever, pus, severe pain, black skin, or rash near eyes needs urgent review.'],
                'hospital_needles' => ['Dermatology', 'Emergency', 'Pharmacy'],
            ],
            'eye' => [
                'label' => 'Eye',
                'specialists' => ['Ophthalmology', 'Emergency Medicine'],
                'library_query' => 'eye redness',
                'red_flags' => ['Sudden vision loss, severe eye pain, eye injury, chemical exposure, or light sensitivity needs emergency care.'],
                'hospital_needles' => ['Ophthalmology', 'Emergency'],
            ],
            'chest' => [
                'label' => 'Chest or breathing',
                'specialists' => ['Internal Medicine', 'Cardiology', 'Emergency Medicine'],
                'library_query' => 'cough',
                'red_flags' => ['Chest pressure, blue lips, severe shortness of breath, coughing blood, or oxygen drop needs emergency care.'],
                'hospital_needles' => ['Emergency', 'Radiology', 'ICU'],
            ],
            'abdomen' => [
                'label' => 'Abdomen or digestion',
                'specialists' => ['Internal Medicine', 'General Surgery'],
                'library_query' => 'abdominal pain',
                'red_flags' => ['Severe belly pain, blood in stool/vomit, fainting, rigid abdomen, or persistent vomiting needs urgent care.'],
                'hospital_needles' => ['Emergency', 'Lab', 'Radiology'],
            ],
            'head_neuro' => [
                'label' => 'Head, brain, or nerves',
                'specialists' => ['Neurology', 'Emergency Medicine'],
                'library_query' => 'headache',
                'red_flags' => ['Face drooping, arm weakness, speech trouble, seizure, confusion, worst headache, or neck stiffness needs emergency care.'],
                'hospital_needles' => ['Emergency', 'Radiology', 'ICU'],
            ],
            'bones_joints' => [
                'label' => 'Bones, joints, or injury',
                'specialists' => ['Orthopedics', 'Radiology'],
                'library_query' => 'joint pain',
                'red_flags' => ['Deformity, inability to bear weight, numbness, open wound, severe swelling, or fever with joint pain needs urgent care.'],
                'hospital_needles' => ['Radiology', 'Emergency', 'OT'],
            ],
            'pregnancy' => [
                'label' => 'Pregnancy or reproductive health',
                'specialists' => ['Obstetrics & Gynecology', 'Emergency Medicine'],
                'library_query' => 'pregnancy pain',
                'red_flags' => ['Pregnancy with bleeding, severe headache, vision changes, severe belly pain, fainting, or reduced fetal movement needs urgent care.'],
                'hospital_needles' => ['Maternity', 'Emergency', 'Lab'],
            ],
            'child' => [
                'label' => 'Child health',
                'specialists' => ['Pediatrics', 'Family Medicine'],
                'library_query' => 'fever child',
                'red_flags' => ['Infant fever, poor feeding, breathing difficulty, dehydration, seizure, blue lips, or unusual sleepiness needs urgent care.'],
                'hospital_needles' => ['Emergency', 'Pediatrics', 'Lab'],
            ],
            'mental_health' => [
                'label' => 'Mental health',
                'specialists' => ['Psychiatry', 'Family Medicine'],
                'library_query' => 'anxiety depression',
                'red_flags' => ['Thoughts of self-harm, harming others, confusion, severe agitation, or unsafe behavior needs immediate emergency support.'],
                'hospital_needles' => ['Emergency', 'Psychiatry'],
            ],
        ];
    }

    private function conditionRules(): array
    {
        return [
            ['name' => 'Skin inflammation or allergy pattern', 'summary' => 'Itching, rash, scaling, or redness can fit allergy, dermatitis, eczema, psoriasis, fungal infection, or irritation.', 'body_parts' => ['skin'], 'keywords' => ['rash', 'itch', 'itching', 'eczema', 'dermatitis', 'red skin', 'scaly', 'lesion', 'spots', 'fungal', 'acne', 'burn'], 'specialists' => ['Dermatology'], 'library_query' => 'eczema'],
            ['name' => 'Eye infection or irritation pattern', 'summary' => 'Redness, discharge, pain, or blurred vision may need eye examination to separate infection, allergy, injury, or pressure problems.', 'body_parts' => ['eye'], 'keywords' => ['eye', 'red eye', 'vision', 'blurred', 'discharge', 'itchy eye', 'conjunctivitis', 'pain eye'], 'specialists' => ['Ophthalmology'], 'library_query' => 'eye redness'],
            ['name' => 'Respiratory infection or breathing concern', 'summary' => 'Cough, fever, sore throat, wheeze, or shortness of breath may relate to viral illness, flu/COVID, asthma flare, pneumonia, or other chest conditions.', 'body_parts' => ['chest', 'general', 'child'], 'keywords' => ['cough', 'fever', 'breath', 'shortness', 'wheeze', 'sore throat', 'chest', 'covid', 'flu', 'pneumonia', 'xray', 'x-ray'], 'specialists' => ['Internal Medicine', 'Family Medicine', 'Radiology'], 'library_query' => 'influenza'],
            ['name' => 'Heart or circulation warning pattern', 'summary' => 'Chest pressure, palpitations, fainting, swelling, or exertional breathlessness needs timely medical assessment.', 'body_parts' => ['chest', 'general'], 'keywords' => ['chest pain', 'pressure', 'palpitation', 'heart', 'left arm', 'sweat', 'faint', 'dizzy', 'swelling'], 'specialists' => ['Cardiology', 'Emergency Medicine'], 'library_query' => 'chest pain'],
            ['name' => 'Digestive or abdominal concern', 'summary' => 'Abdominal pain, vomiting, diarrhea, acidity, bloating, or jaundice can come from infection, inflammation, gallbladder/liver issues, or surgical causes.', 'body_parts' => ['abdomen', 'general'], 'keywords' => ['abdomen', 'stomach', 'belly', 'vomit', 'diarrhea', 'constipation', 'jaundice', 'liver', 'acid', 'bloating', 'blood stool'], 'specialists' => ['Internal Medicine', 'General Surgery'], 'library_query' => 'abdominal pain'],
            ['name' => 'Neurology or severe headache concern', 'summary' => 'Headache, weakness, numbness, seizure, dizziness, or confusion needs careful neurological assessment, especially if sudden or severe.', 'body_parts' => ['head_neuro'], 'keywords' => ['headache', 'migraine', 'numb', 'weakness', 'seizure', 'confusion', 'dizzy', 'stroke', 'neck stiff', 'memory'], 'specialists' => ['Neurology', 'Emergency Medicine'], 'library_query' => 'meningitis'],
            ['name' => 'Bone, joint, or injury concern', 'summary' => 'Pain, swelling, injury, deformity, or limited movement may need examination and imaging to assess fracture, sprain, arthritis, or infection.', 'body_parts' => ['bones_joints'], 'keywords' => ['joint', 'bone', 'fracture', 'sprain', 'injury', 'swelling', 'back pain', 'knee', 'shoulder', 'xray', 'x-ray'], 'specialists' => ['Orthopedics', 'Radiology'], 'library_query' => 'joint pain'],
            ['name' => 'Pregnancy or gynecology concern', 'summary' => 'Pregnancy symptoms, pelvic pain, bleeding, discharge, cycle changes, or reproductive concerns should be reviewed by obstetrics/gynecology.', 'body_parts' => ['pregnancy'], 'keywords' => ['pregnancy', 'pregnant', 'bleeding', 'pelvic', 'period', 'cramps', 'discharge', 'fetal', 'baby movement'], 'specialists' => ['Obstetrics & Gynecology'], 'library_query' => 'preeclampsia'],
            ['name' => 'Child fever or pediatric concern', 'summary' => 'Children with fever, poor feeding, rash, cough, dehydration, or unusual sleepiness need age-specific assessment.', 'body_parts' => ['child'], 'keywords' => ['child', 'baby', 'infant', 'pediatric', 'fever', 'feeding', 'rash', 'crying', 'dehydration'], 'specialists' => ['Pediatrics'], 'library_query' => 'respiratory syncytial virus'],
            ['name' => 'Mental health support concern', 'summary' => 'Anxiety, low mood, panic, sleep issues, stress, or unsafe thoughts should be handled with supportive mental health care.', 'body_parts' => ['mental_health'], 'keywords' => ['anxiety', 'depression', 'panic', 'stress', 'sleep', 'sad', 'self harm', 'suicide', 'anger', 'hallucination'], 'specialists' => ['Psychiatry'], 'library_query' => 'mental health'],
        ];
    }

    private function conditionMatches(string $context, string $bodyPart): array
    {
        $matches = [];
        foreach ($this->conditionRules() as $rule) {
            $score = in_array($bodyPart, $rule['body_parts'], true) ? 2 : 0;
            foreach ($rule['keywords'] as $keyword) {
                if ($keyword !== '' && str_contains($context, $keyword)) {
                    $score += 1;
                }
            }

            if ($score > 0) {
                $rule['score'] = $score;
                $matches[] = $rule;
            }
        }

        usort($matches, static fn(array $a, array $b): int => ($b['score'] <=> $a['score']) ?: strcmp($a['name'], $b['name']));
        return $matches ?: [[
            'name' => 'General health concern',
            'summary' => 'Symptoms are not specific enough for a narrow match. Start with a general physician who can examine and order tests if needed.',
            'specialists' => ['Family Medicine', 'Internal Medicine'],
            'library_query' => 'disease',
            'score' => 1,
        ]];
    }

    private function urgentSignals(string $context): array
    {
        $signals = [
            'chest pain' => 'Chest pain or pressure should be treated as urgent, especially with sweating, nausea, arm/jaw pain, or breathlessness.',
            'shortness of breath' => 'Shortness of breath, blue lips, or severe wheezing needs urgent medical care.',
            'breathing' => 'Severe breathing difficulty needs urgent medical care.',
            'faint' => 'Fainting, collapse, or repeated dizziness needs urgent assessment.',
            'confusion' => 'Confusion, seizure, or sudden behavior change needs emergency review.',
            'seizure' => 'A seizure or repeated seizure activity needs emergency care.',
            'stroke' => 'Face drooping, arm weakness, or speech trouble needs emergency stroke care.',
            'blood' => 'Coughing, vomiting, or passing blood should be reviewed urgently.',
            'suicide' => 'Self-harm or suicide thoughts need immediate emergency or crisis support.',
            'self harm' => 'Self-harm thoughts need immediate emergency or crisis support.',
            'pregnant bleeding' => 'Bleeding during pregnancy needs urgent obstetric assessment.',
            'vision loss' => 'Sudden vision loss or severe eye pain needs urgent eye care.',
            'worst headache' => 'A sudden worst-ever headache needs emergency assessment.',
        ];

        $found = [];
        foreach ($signals as $keyword => $message) {
            if (str_contains($context, $keyword)) {
                $found[] = $message;
            }
        }

        return array_values(array_unique($found));
    }

    private function libraryMatches(array $conditions, string $fallbackQuery, string $symptoms): array
    {
        $queries = array_filter(array_unique(array_merge(
            array_column($conditions, 'library_query'),
            [$fallbackQuery, strtok($symptoms, ' ') ?: '']
        )));

        $results = [];
        $seen = [];
        $catalog = new DiseaseInformation();
        foreach ($queries as $query) {
            foreach (array_slice($catalog->search((string) $query), 0, 2) as $row) {
                $slug = (string) ($row['slug'] ?? '');
                if ($slug === '' || isset($seen[$slug])) {
                    continue;
                }
                $seen[$slug] = true;
                $results[] = [
                    'name' => $row['disease_name'] ?? 'Disease guide',
                    'risk_level' => $row['risk_level'] ?? 'Review',
                    'summary' => trim(strip_tags((string) ($row['overview'] ?? $row['symptoms'] ?? 'Review this condition guide with your clinician.'))),
                    'url' => route_url('disease', ['slug' => $slug]),
                ];
                if (count($results) >= 4) {
                    return $results;
                }
            }
        }

        return $results;
    }

    private function providerRecommendations(array $specialists, string $bodyPart): array
    {
        $doctors = [];
        $seenDoctors = [];
        $doctorModel = new Doctor();
        $appointmentIndex = $this->patientAppointmentIndex(Auth::id());

        foreach ($specialists as $specialty) {
            foreach ($doctorModel->search(['specialization' => $specialty, 'active_only' => true, 'verified_only' => true]) as $doctor) {
                $key = (int) ($doctor['user_id'] ?? 0);
                if (!$key || isset($seenDoctors[$key])) {
                    continue;
                }
                $seenDoctors[$key] = true;
                $doctors[] = $this->doctorCardPayload($doctor, $appointmentIndex);
                if (count($doctors) >= 4) {
                    break 2;
                }
            }
        }

        if (count($doctors) < 3) {
            foreach ($doctorModel->search(['active_only' => true, 'verified_only' => true]) as $doctor) {
                $key = (int) ($doctor['user_id'] ?? 0);
                if (!$key || isset($seenDoctors[$key])) {
                    continue;
                }
                $seenDoctors[$key] = true;
                $doctors[] = $this->doctorCardPayload($doctor, $appointmentIndex);
                if (count($doctors) >= 4) {
                    break;
                }
            }
        }

        $hospitals = $this->hospitalRecommendations($specialists, $bodyPart);
        return ['doctors' => $doctors, 'hospitals' => $hospitals];
    }

    private function consultationDoctorDirectory(): array
    {
        $appointmentIndex = $this->patientAppointmentIndex(Auth::id());
        $doctors = [];
        foreach ((new Doctor())->search(['active_only' => true, 'verified_only' => true]) as $doctor) {
            $doctors[] = $this->doctorCardPayload($doctor, $appointmentIndex);
            if (count($doctors) >= 8) {
                break;
            }
        }

        return $doctors;
    }

    private function patientAppointmentIndex(?int $patientId): array
    {
        if (!$patientId) {
            return [];
        }

        $index = [];
        foreach ((new Appointment())->forPatient($patientId) as $appointment) {
            $doctorId = (int) ($appointment['doctor_id'] ?? 0);
            $status = (string) ($appointment['status'] ?? '');
            $date = (string) ($appointment['date'] ?? '');
            if (!$doctorId || isset($index[$doctorId])) {
                continue;
            }
            if (in_array($status, ['cancelled', 'completed'], true)) {
                continue;
            }
            if ($date !== '' && $date < date('Y-m-d')) {
                continue;
            }
            $index[$doctorId] = (int) ($appointment['id'] ?? 0);
        }

        return $index;
    }

    private function doctorCardPayload(array $doctor, array $appointmentIndex = []): array
    {
        $doctorId = (int) ($doctor['user_id'] ?? 0);
        $bookingParams = ['book' => '1', 'doctor_id' => (int) ($doctor['user_id'] ?? 0)];
        if (!empty($doctor['hospital_id'])) {
            $bookingParams['hospital_id'] = (int) $doctor['hospital_id'];
        }

        $phone = trim((string) ($doctor['hospital_phone'] ?? ''));
        $appointmentId = $doctorId > 0 ? (int) ($appointmentIndex[$doctorId] ?? 0) : 0;
        return [
            'user_id' => $doctorId,
            'name' => $doctor['name'] ?? 'Doctor',
            'specialization' => $doctor['specialization'] ?? 'General',
            'hospital_name' => $doctor['hospital_name'] ?? 'Independent clinic',
            'phone' => $phone !== '' ? $phone : 'Not listed',
            'coordinates' => $doctor['hospital_coordinates'] ?? '',
            'city' => $doctor['hospital_city'] ?? '',
            'country' => $doctor['hospital_country'] ?? '',
            'availability_status' => $doctor['availability_status'] ?? 'offline',
            'availability_updated_at' => $doctor['availability_updated_at'] ?? '',
            'experience' => (int) ($doctor['experience'] ?? 0),
            'fee' => (float) ($doctor['consultation_fee'] ?? 0),
            'book_url' => route_url('appointments', $bookingParams),
            'appointment_id' => $appointmentId,
            'consultation_create_url' => route_url('consultations/create'),
        ];
    }

    private function hospitalRecommendations(array $specialists, string $bodyPart): array
    {
        $bodyMap = $this->bodyPartMap();
        $needles = array_map('strtolower', array_merge($specialists, $bodyMap[$bodyPart]['hospital_needles'] ?? []));
        $ranked = [];
        foreach ((new Hospital())->all() as $hospital) {
            $haystack = strtolower(implode(' ', [
                $hospital['name'] ?? '',
                $hospital['facilities'] ?? '',
                $hospital['departments'] ?? '',
                $hospital['city'] ?? '',
                $hospital['country'] ?? '',
            ]));
            $score = ($hospital['verified_status'] ?? '') === 'verified' ? 4 : 0;
            foreach ($needles as $needle) {
                if ($needle !== '' && str_contains($haystack, $needle)) {
                    $score += 2;
                }
            }
            $hospital['_score'] = $score;
            $ranked[] = $hospital;
        }

        usort($ranked, static fn(array $a, array $b): int => ($b['_score'] <=> $a['_score']) ?: strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? '')));
        return array_map(static function (array $hospital): array {
            return [
                'name' => $hospital['name'] ?? 'Hospital',
                'city' => $hospital['city'] ?? '',
                'country' => $hospital['country'] ?? '',
                'phone' => trim((string) ($hospital['phone'] ?? '')) ?: 'Not listed',
                'coordinates' => $hospital['coordinates'] ?? '',
                'facilities' => $hospital['facilities'] ?? '',
                'verified_status' => $hospital['verified_status'] ?? 'pending',
                'map_url' => route_url('map', ['nearby' => '1', 'focus' => 'hospital']),
            ];
        }, array_slice($ranked, 0, 3));
    }

    private function nextSteps(string $mode, string $urgency, array $specialists): array
    {
        $steps = [];
        if ($urgency === 'urgent') {
            $steps[] = 'If any red flag is present now, seek emergency care or call local emergency services.';
        } elseif ($urgency === 'soon') {
            $steps[] = 'Book a doctor review within 24-48 hours, sooner if symptoms are worsening.';
        } else {
            $steps[] = 'Book a routine appointment with ' . ($specialists[0] ?? 'a qualified doctor') . ' for confirmation.';
        }

        $steps[] = $mode === 'image'
            ? 'Keep the original image and take a clearer follow-up photo in good light if the area changes.'
            : 'Write symptom start date, severity, temperature, medicines taken, allergies, and previous reports before the visit.';
        $steps[] = 'Do not start, stop, or change prescription medicine only because of AI output.';
        return $steps;
    }

    private function summaryFor(string $mode, array $primary, string $bodyLabel, string $urgency): string
    {
        $prefix = $mode === 'image'
            ? 'The uploaded ' . strtolower($bodyLabel) . ' image and details suggest a care direction, not a diagnosis.'
            : 'Your written symptoms suggest a care direction, not a diagnosis.';
        $urgencyText = $urgency === 'urgent'
            ? 'Red flags were detected, so urgent medical review is recommended.'
            : ($urgency === 'soon' ? 'Symptoms may need timely review if persistent or worsening.' : 'This looks suitable for planned clinical review unless red flags appear.');

        return $prefix . ' Most relevant track: ' . $primary['name'] . '. ' . $urgencyText;
    }

    private function consumeAnalysisQuota(int $userId): array
    {
        $key = '_scanner_usage_' . $userId;
        $now = time();
        $windowStart = $now - 3600;
        $events = array_values(array_filter((array) ($_SESSION[$key] ?? []), static fn($timestamp): bool => (int) $timestamp >= $windowStart));

        if (count($events) >= self::HOURLY_ANALYSIS_LIMIT) {
            $_SESSION[$key] = $events;
            return ['allowed' => false, 'remaining' => 0];
        }

        $events[] = $now;
        $_SESSION[$key] = $events;
        return ['allowed' => true, 'remaining' => max(0, self::HOURLY_ANALYSIS_LIMIT - count($events))];
    }

    private function normalizeJsonPayload(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $decoded = json_decode($value, true);
        return is_array($decoded) ? json_encode($decoded, JSON_UNESCAPED_SLASHES) : '';
    }

    private function limitText(string $value, int $limit): string
    {
        $value = trim(preg_replace('/\s+/', ' ', $value) ?? '');
        return mb_strlen($value) > $limit ? mb_substr($value, 0, $limit) : $value;
    }

    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }
}
