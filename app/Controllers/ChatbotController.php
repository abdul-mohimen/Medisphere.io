<?php
namespace App\Controllers;

use App\Core\CSRF;
use App\Models\CmsArticle;
use App\Models\CmsFaq;
use App\Models\DiseaseInformation;
use App\Models\Doctor;
use App\Models\Hospital;
use Throwable;

class ChatbotController
{
    private const MAX_ACTIONS = 3;

    public function ask(): void
    {
        header('Content-Type: application/json');

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
            return;
        }

        $question = $this->cleanQuestion((string) ($_POST['message'] ?? ''));
        if ($question === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Please type a question.']);
            return;
        }

        $history = $this->cleanHistory((string) ($_POST['history'] ?? '[]'));
        $context = $this->collectProjectContext($question);
        $modelAnswer = $this->askConfiguredModel($question, $context, $history);
        $reply = $this->composeProjectAnswer($question, $context, $modelAnswer !== null);

        if ($modelAnswer !== null) {
            $reply['answer'] = trim($modelAnswer) . "\n\n" . $reply['answer'];
            $reply['source'] = 'AI model + MediSphere project search';
        }

        echo json_encode(array_merge(['success' => true], $reply), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function cleanQuestion(string $value): string
    {
        $question = trim(preg_replace('/\s+/', ' ', strip_tags($value)) ?? '');
        return substr($question, 0, 700);
    }

    private function cleanHistory(string $json): array
    {
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return [];
        }

        $history = [];
        foreach (array_slice($decoded, -8) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $role = in_array(($item['role'] ?? ''), ['user', 'assistant'], true) ? $item['role'] : 'user';
            $text = $this->cleanQuestion((string) ($item['text'] ?? ''));
            if ($text !== '') {
                $history[] = ['role' => $role, 'text' => substr($text, 0, 500)];
            }
        }

        return $history;
    }

    private function collectProjectContext(string $question): array
    {
        $terms = $this->searchTerms($question);

        return [
            'intents' => $this->detectIntents($question),
            'doctors' => $this->searchModel(fn(string $term): array => (new Doctor())->searchPublic($term), $terms, ['user_id', 'id'], 3),
            'hospitals' => $this->searchModel(fn(string $term): array => (new Hospital())->searchPublic($term), $terms, ['id'], 3),
            'faqs' => $this->searchModel(fn(string $term): array => (new CmsFaq())->searchPublic($term), $terms, ['id'], 3),
            'articles' => $this->searchModel(fn(string $term): array => (new CmsArticle())->searchPublic($term), $terms, ['id'], 3),
            'diseases' => $this->searchModel(fn(string $term): array => (new DiseaseInformation())->search($term), $terms, ['id'], 3),
        ];
    }

    private function searchTerms(string $question): array
    {
        $terms = [$question];
        $stopWords = [
            'about', 'after', 'again', 'also', 'appointment', 'appointments', 'best', 'book', 'care', 'create',
            'doctor', 'doctors', 'does', 'find', 'from', 'give', 'health', 'healthcare', 'help', 'hospital',
            'hospitals', 'into', 'near', 'please', 'question', 'report', 'reports', 'search', 'show', 'tell',
            'that', 'this', 'what', 'when', 'where', 'with', 'your',
        ];
        $tokens = preg_split('/[^a-z0-9]+/i', strtolower($question)) ?: [];
        foreach ($tokens as $token) {
            if (strlen($token) < 3 || in_array($token, $stopWords, true)) {
                continue;
            }
            $terms[] = $token;
        }

        return array_values(array_unique($terms));
    }

    private function detectIntents(string $question): array
    {
        $q = strtolower($question);
        $intents = [];
        $checks = [
            'greeting' => '/\b(hi|hello|hey|salam|assalam|good morning|good afternoon|good evening|how are you)\b/',
            'thanks' => '/\b(thank|thanks|appreciate|shukriya|jazak)\b/',
            'capabilities' => '/what can you do|who are you|assistant|chatbot|help me|features|guide me/',
            'appointment' => '/appointment|book|schedule|slot|visit|doctor appointment|book.*doctor|consult a doctor/',
            'reports' => '/report|document|upload|lab|prescription|file/',
            'emergency' => '/emergency|urgent|ambulance|trauma|accident|critical/',
            'map' => '/map|near|nearby|location|route|direction|hospital|clinic|pharmacy|facility/',
            'scanner' => '/scanner|scan|ai|image|x-ray|xray|skin|eye|symptom|disease/',
            'consultation' => '/video|consultation|telemedicine|call|meeting/',
            'messages' => '/message|chat|inbox|contact/',
            'auth' => '/login|sign in|register|signup|account|doctor register|hospital register/',
            'provider_registration' => '/doctor register|hospital register|provider|join as|verification|verify|license|registration number/',
            'hospital_ops' => '/bed|inventory|department|assignment|hospital management|ward|stock/',
            'password' => '/forgot|reset password|password|recover/',
            'language' => '/language|locale|urdu|hindi|arabic|english|spanish|french|german|chinese|translate/',
            'newsletter' => '/newsletter|subscribe|email update|updates/',
            'support' => '/support|contact|helpdesk|complaint|problem|issue|not working|broken/',
            'dashboard' => '/dashboard|profile|health profile|history|allergy|medication|insurance|metric/',
            'payments' => '/payment|invoice|bill|refund|checkout/',
            'policy' => '/privacy|policy|terms|security|consent|compliance/',
        ];

        foreach ($checks as $intent => $pattern) {
            if (preg_match($pattern, $q)) {
                $intents[] = $intent;
            }
        }

        if (in_array('provider_registration', $intents, true) && !preg_match('/map|near|nearby|location|route|direction|pharmacy|facility|clinic/', $q)) {
            $intents = array_values(array_filter($intents, static fn(string $intent): bool => $intent !== 'map'));
        }

        return array_values(array_unique($intents));
    }

    private function searchModel(callable $callback, array $terms, array $keyFields, int $limit): array
    {
        $items = [];
        foreach ($terms as $term) {
            try {
                $rows = $callback($term);
            } catch (Throwable) {
                $rows = [];
            }

            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $key = $this->rowKey($row, $keyFields);
                if (!isset($items[$key])) {
                    $items[$key] = $row;
                }
                if (count($items) >= $limit) {
                    break 2;
                }
            }
        }

        return array_values($items);
    }

    private function rowKey(array $row, array $fields): string
    {
        foreach ($fields as $field) {
            if (!empty($row[$field])) {
                return $field . ':' . $row[$field];
            }
        }

        return md5(json_encode($row));
    }

    private function composeProjectAnswer(string $question, array $context, bool $modelUsed): array
    {
        $intents = $context['intents'];
        $parts = [];
        $cards = [];

        if (in_array('greeting', $intents, true)) {
            $parts[] = 'Hey, I’m here. Tell me what you want to do and I’ll keep it simple: find care, book an appointment, upload reports, open the map, explain the scanner, or help with an account issue.';
        }

        if (in_array('thanks', $intents, true)) {
            $parts[] = 'You’re welcome. I’m still here if you want the next step, like opening the right page or narrowing a doctor/hospital search.';
        }

        if (in_array('capabilities', $intents, true)) {
            $parts[] = 'I can answer MediSphere project questions, search doctors and hospitals, point you to the right module, explain reports, appointments, messages, video consultations, payments, privacy, and the AI scanner. I can also turn your question into quick action links.';
            $cards[] = $this->card('Find healthcare', 'Search doctors, hospitals, specialties, fees, and care areas.', route_url('find-healthcare'), 'Care');
            $cards[] = $this->card('FAQ center', 'Open common platform support answers.', route_url('faq'), 'FAQ');
        }

        if (in_array('emergency', $intents, true)) {
            $parts[] = 'For urgent or life-threatening symptoms, call your local emergency number immediately. After that, the emergency map can help you locate nearby hospitals and urgent-care support.';
            $cards[] = $this->card('Emergency map', 'Open nearby emergency and hospital markers.', route_url('map', ['nearby' => 1, 'focus' => 'emergency']) . '#healthcareExplorerMap', 'Emergency');
        }

        if (in_array('appointment', $intents, true)) {
            $parts[] = 'For booking, I’d do it in this order: search a doctor if needed, open Appointments, pick the doctor/date/time, add symptoms, then submit. If the appointment becomes eligible, you can continue into consultation.';
            $cards[] = $this->card('Book appointment', 'Create or review appointment requests.', route_url('appointments', ['book' => '1']), 'Appointments');
            $cards[] = $this->card('Find doctors', 'Search doctors, hospitals, specialties, and fees.', route_url('find-healthcare'), 'Doctors');
        }

        if (in_array('reports', $intents, true)) {
            $parts[] = 'For reports, open Reports, choose the report category, upload the file, and save it. Keep private documents inside the Reports module instead of normal chat.';
            $cards[] = $this->card('Upload reports', 'Store lab reports, prescriptions, and documents.', route_url('reports'), 'Reports');
        }

        if (in_array('map', $intents, true)) {
            $parts[] = 'For locations, the interactive map can show hospitals, doctor clinics, pharmacies, and emergency support. Use the filter buttons on the map to switch marker types.';
            $cards[] = $this->card('Open healthcare map', 'Search globally and render nearby care markers.', route_url('map', ['nearby' => 1]) . '#healthcareExplorerMap', 'Map');
        }

        if (in_array('scanner', $intents, true)) {
            $parts[] = 'The AI scanner supports image-based screening workflows. Treat results as guidance only; a licensed clinician should confirm any medical concern.';
            $cards[] = $this->card('AI scanner', 'Upload supported medical images for screening support.', route_url('scanner'), 'AI');
            $cards[] = $this->card('Disease library', 'Read patient-friendly disease information.', route_url('diseases'), 'Library');
        }

        if (in_array('consultation', $intents, true)) {
            $parts[] = 'Video consultation is available from the Consultations area after an eligible appointment is created.';
            $cards[] = $this->card('Consultations', 'Open waiting room and consultation sessions.', route_url('consultations'), 'Video');
        }

        if (in_array('messages', $intents, true)) {
            $parts[] = 'Use Messages to chat with connected care contacts. The assistant can guide you, while private patient conversations stay in the Messages module.';
            $cards[] = $this->card('Messages', 'Open patient, doctor, and hospital chats.', route_url('messages'), 'Chat');
        }

        if (in_array('provider_registration', $intents, true)) {
            $parts[] = 'For doctor or hospital registration, start from Register, choose the correct role, complete profile details, and submit license or hospital registration information. Admin verification controls whether the provider is fully trusted in the platform.';
            $cards[] = $this->card('Register provider', 'Create a doctor or hospital account.', route_url('register'), 'Register');
            $cards[] = $this->card('Verification center', 'Admin review area for doctors and hospitals.', route_url('admin/verifications'), 'Admin');
        }

        if (in_array('auth', $intents, true)) {
            $parts[] = 'Patients, doctors, and hospitals can register from the account pages. Doctors and hospitals may need verification before all features are available.';
            $cards[] = $this->card('Register', 'Create a patient, doctor, or hospital account.', route_url('register'), 'Account');
            $cards[] = $this->card('Login', 'Sign in to your MediSphere portal.', route_url('login'), 'Login');
        }

        if (in_array('password', $intents, true)) {
            $parts[] = 'For password trouble, use Reset Password from the login flow. Enter your email, follow the reset link, then sign in again with the new password.';
            $cards[] = $this->card('Reset password', 'Start account recovery from the reset page.', route_url('reset-password'), 'Account');
        }

        if (in_array('dashboard', $intents, true)) {
            $parts[] = 'Your dashboard is the quick overview. For deeper patient details, Health Profile keeps history, allergies, medications, emergency contacts, insurance, family history, and health metrics together.';
            $cards[] = $this->card('Dashboard', 'Open your account overview.', route_url('dashboard'), 'Portal');
            $cards[] = $this->card('Health profile', 'Manage health history and patient details.', route_url('profile'), 'Profile');
        }

        if (in_array('hospital_ops', $intents, true)) {
            $parts[] = 'Hospital management covers departments, inventory, bed units, doctor requests, assignments, and patient record access. It is designed for hospital accounts and admin workflows.';
            $cards[] = $this->card('Hospital management', 'Manage beds, departments, inventory, and assignments.', route_url('hospital-management'), 'Hospital');
        }

        if (in_array('payments', $intents, true)) {
            $parts[] = 'Payments and invoices live in the Payments area. You can review invoices, checkout status, and refund requests there.';
            $cards[] = $this->card('Payments', 'Review bills, invoices, and checkout flow.', route_url('payments'), 'Billing');
        }

        if (in_array('language', $intents, true)) {
            $parts[] = 'Language can be changed from the top bar selector. The project already includes multiple locales, including English, Urdu, Hindi, Arabic, Spanish, French, German, and Chinese.';
        }

        if (in_array('newsletter', $intents, true)) {
            $parts[] = 'Newsletter signup is on the home page. Add your email there to receive platform and health-content updates when the email service is connected.';
            $cards[] = $this->card('Home', 'Open the newsletter signup area.', route_url('home'), 'Updates');
        }

        if (in_array('support', $intents, true)) {
            $parts[] = 'If something feels stuck, tell me the page name and what happened. For self-service help, the FAQ and Guidelines pages are the best first places to check.';
            $cards[] = $this->card('FAQ center', 'Search common support answers.', route_url('faq'), 'Help');
            $cards[] = $this->card('Guidelines', 'Read platform usage guidance.', route_url('guidelines'), 'Guide');
        }

        if (in_array('policy', $intents, true)) {
            $parts[] = 'Privacy, consent, and compliance information is available through the policy and compliance pages.';
            $cards[] = $this->card('Privacy policy', 'Read platform privacy information.', route_url('policy', ['slug' => 'privacy-policy']), 'Policy');
            $cards[] = $this->card('Compliance center', 'Manage consent and privacy requests.', route_url('compliance-center'), 'Compliance');
        }

        $this->appendSearchFindings($context, $parts, $cards);

        if (!$parts) {
            $parts[] = 'I searched the MediSphere project knowledge base but did not find an exact match. Try asking about appointments, doctors, hospitals, reports, AI scanner, emergency map, payments, login, or privacy.';
            $cards[] = $this->card('Browse articles', 'Open public health articles and platform news.', route_url('blog'), 'Articles');
            $cards[] = $this->card('FAQ', 'Open common support questions.', route_url('faq'), 'Help');
        }

        $cards = $this->uniqueCards($cards);
        $answer = implode("\n\n", $parts);
        if (!$modelUsed && $answer !== '') {
            $answer = $this->humanizeAnswer($answer, $intents);
        }

        return [
            'answer' => $answer,
            'cards' => array_slice($cards, 0, self::MAX_ACTIONS),
            'suggestions' => array_slice($this->suggestions($intents), 0, self::MAX_ACTIONS),
            'source' => $modelUsed ? 'MediSphere project search' : 'MediSphere project search',
        ];
    }

    private function appendSearchFindings(array $context, array &$parts, array &$cards): void
    {
        if (!empty($context['doctors'])) {
            $summary = array_map(function (array $doctor): string {
                $line = $doctor['name'] ?? 'Doctor';
                if (!empty($doctor['specialization'])) {
                    $line .= ' - ' . $doctor['specialization'];
                }
                if (!empty($doctor['hospital_name'])) {
                    $line .= ' at ' . $doctor['hospital_name'];
                }
                return $line;
            }, $context['doctors']);
            $parts[] = 'Doctor matches: ' . implode('; ', $summary) . '.';
            $cards[] = $this->card('Doctor directory', 'Compare doctors by specialty, hospital, and fee.', route_url('find-healthcare'), 'Doctors');
        }

        if (!empty($context['hospitals'])) {
            $summary = array_map(function (array $hospital): string {
                return trim(($hospital['name'] ?? 'Hospital') . ' - ' . ($hospital['city'] ?? '') . ', ' . ($hospital['country'] ?? ''), ' -,');
            }, $context['hospitals']);
            $parts[] = 'Hospital matches: ' . implode('; ', $summary) . '.';
            $cards[] = $this->card('Hospital map', 'Open hospital markers on the live map.', route_url('map', ['nearby' => 1, 'focus' => 'hospital']) . '#healthcareExplorerMap', 'Hospitals');
        }

        if (!empty($context['faqs'])) {
            $faq = $context['faqs'][0];
            $parts[] = 'FAQ match: ' . ($faq['question'] ?? 'Question') . ' - ' . $this->snippet((string) ($faq['answer'] ?? ''), 220);
            $cards[] = $this->card('FAQ center', 'Read more support answers.', route_url('faq'), 'FAQ');
        }

        if (!empty($context['diseases'])) {
            $disease = $context['diseases'][0];
            $parts[] = 'Health library match: ' . ($disease['disease_name'] ?? 'Disease information') . ' - ' . $this->snippet((string) ($disease['overview'] ?? $disease['symptoms'] ?? ''), 220);
            if (!empty($disease['slug'])) {
                $cards[] = $this->card($disease['disease_name'] ?? 'Disease information', 'Open disease library details.', route_url('disease', ['slug' => $disease['slug']]), 'Library');
            }
        }

        if (!empty($context['articles'])) {
            foreach (array_slice($context['articles'], 0, 2) as $article) {
                if (empty($article['slug'])) {
                    continue;
                }
                $cards[] = $this->card($article['title'] ?? 'Health article', $this->snippet((string) ($article['excerpt'] ?? $article['content'] ?? ''), 120), route_url('article', ['slug' => $article['slug']]), 'Article');
            }
        }
    }

    private function card(string $title, string $text, string $url, string $tag): array
    {
        return compact('title', 'text', 'url', 'tag');
    }

    private function humanizeAnswer(string $answer, array $intents): string
    {
        if (in_array('thanks', $intents, true) && count($intents) === 1) {
            return $answer;
        }

        if (in_array('greeting', $intents, true)) {
            return $answer;
        }

        if (in_array('emergency', $intents, true)) {
            return $answer;
        }

        $openers = [
            'Got it. Here’s the clearest path:',
            'Absolutely. I’d handle it like this:',
            'Yes, I can help with that. Here’s the useful part:',
            'Sure. Here’s what matters right now:',
        ];

        $index = abs((int) crc32(implode('|', $intents) ?: $answer)) % count($openers);
        return $openers[$index] . "\n\n" . $answer;
    }

    private function uniqueCards(array $cards): array
    {
        $seen = [];
        $unique = [];
        foreach ($cards as $card) {
            $key = $card['url'] ?? md5(json_encode($card));
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $unique[] = $card;
        }
        return $unique;
    }

    private function suggestions(array $intents): array
    {
        if (in_array('provider_registration', $intents, true)) {
            return ['How does doctor verification work?', 'How can a hospital register?', 'Open admin verification center'];
        }
        if (in_array('emergency', $intents, true)) {
            return ['Show emergency hospitals on map', 'Find nearby pharmacies', 'How do I book urgent follow-up?'];
        }
        if (in_array('appointment', $intents, true)) {
            return ['Find cardiology doctors', 'How do I upload reports?', 'Open video consultation'];
        }
        if (in_array('scanner', $intents, true)) {
            return ['What can the AI scanner do?', 'How do I save scan results?', 'Find a doctor after scan'];
        }
        if (in_array('payments', $intents, true)) {
            return ['Where are invoices?', 'How do refunds work?', 'Open payments page'];
        }
        if (in_array('support', $intents, true)) {
            return ['Open FAQ center', 'How do I reset password?', 'How do I contact a doctor?'];
        }
        if (in_array('greeting', $intents, true) || in_array('capabilities', $intents, true)) {
            return ['Find doctors near me', 'How do I book an appointment?', 'Show emergency hospitals on map'];
        }
        return [
            'How do I book an appointment?',
            'Find doctors near me',
            'Show emergency hospitals on map',
        ];
    }

    private function snippet(string $value, int $limit): string
    {
        $text = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($value))) ?? '');
        if (strlen($text) <= $limit) {
            return $text;
        }
        return rtrim(substr($text, 0, $limit - 1)) . '...';
    }

    private function askConfiguredModel(string $question, array $context, array $history): ?string
    {
        $url = trim((string) config('services.chatbot_model_url', ''));
        if ($url === '') {
            return null;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (!in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        $payload = [
            'question' => $question,
            'history' => $history,
            'context' => $this->compactContextForModel($context),
            'system' => 'You are MediSphere Health Assistant. Answer using the provided project context. Do not diagnose; recommend emergency services for urgent symptoms.',
        ];

        $response = @file_get_contents($url, false, stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAccept: application/json\r\n",
                'content' => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'timeout' => 5,
                'ignore_errors' => true,
            ],
        ]));

        if (!is_string($response) || trim($response) === '') {
            return null;
        }

        $decoded = json_decode($response, true);
        if (is_array($decoded)) {
            $answer = $decoded['answer']
                ?? $decoded['message']
                ?? $decoded['text']
                ?? $decoded['generated_text']
                ?? $decoded['choices'][0]['message']['content']
                ?? null;
            return is_string($answer) && trim($answer) !== '' ? $this->snippet($answer, 1200) : null;
        }

        return $this->snippet($response, 1200);
    }

    private function compactContextForModel(array $context): array
    {
        $compact = ['intents' => $context['intents'] ?? []];
        foreach (['doctors', 'hospitals', 'faqs', 'articles', 'diseases'] as $group) {
            $compact[$group] = array_map(function (array $item): array {
                return array_slice($item, 0, 8, true);
            }, array_slice($context[$group] ?? [], 0, 3));
        }
        return $compact;
    }
}
