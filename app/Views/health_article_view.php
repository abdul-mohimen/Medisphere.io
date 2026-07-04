<?php
$isVideo = ($article['media_type'] ?? '') === 'video';
$isLocked = !empty($article['is_locked']);
$requiredLevel = ucfirst((string) ($article['required_level'] ?? 'premium'));
$heroImage = $article['hero_image'] ?: ($article['video_poster'] ?: $article['card_image']);
$voiceoverUrl = $article['voiceover_url'] ?? null;
$publishedDate = date('F j, Y', strtotime((string) ($article['published_at'] ?: $article['created_at'])));
$relatedArticles = $relatedArticles ?? [];

$editorialOpeningBySlug = [
    'global-care-command-centers' => '<p>The strongest healthcare platforms are judged in the moments between clinical events, when records, appointments, teams, and follow-up decisions must stay aligned. A command-center model gives patients and professionals one dependable operating view, reducing uncertainty while preserving clinical accountability.</p>',
    'consent-first-record-sharing' => '<p>Trust is now the core infrastructure of digital healthcare. Patients will share sensitive records only when the system makes access visible, permission-based, and easy to understand before any hospital, doctor, or support team views their information.</p>',
    'virtual-care-rooms-specialist-access' => '<p>Specialist access is no longer defined only by geography. A well-designed virtual care room can bring clinical expertise closer to patients, but only when video, records, preparation, and follow-up operate as one secure workflow.</p>',
    'ai-triage-report-queues' => '<p>AI becomes valuable in healthcare when it helps clinicians see urgency sooner without pretending to replace clinical judgment. The best triage workflows reduce noise, organize reports, and make the next professional review faster and safer.</p>',
    'digital-intake-safer-appointments' => '<p>A safer appointment begins before the patient enters the room. When symptoms, history, medication details, allergies, and reports are collected with discipline, clinicians can spend more time making decisions and less time reconstructing the story.</p>',
    'patient-record-checklist-family-ready' => '<p>Every family needs a reliable health record system before a stressful moment arrives. A clear, current record set can shorten emergency conversations, prevent missing details, and give clinicians the context they need when time matters.</p>',
    'ai-screening-supports-clinicians' => '<p>AI symptom screening should feel like a disciplined clinical intake assistant, not a diagnosis engine. Its role is to collect better information, surface risk signals, and prepare patients for a more focused conversation with a qualified professional.</p>',
    'finding-right-care-nearby' => '<p>The right care decision depends on clinical fit, location, urgency, cost, and availability all at once. A premium care-discovery experience helps patients compare options calmly before stress turns a routine question into a delayed response.</p>',
    'consent-privacy-modern-healthcare' => '<p>Modern healthcare runs on data, but trust determines whether that data is complete and useful. Consent and privacy are not background policies; they are patient-facing promises that shape whether people feel safe enough to participate honestly.</p>',
    'medication-safety-connected-care' => '<p>Medication safety is one of the most practical measures of care quality. A single accurate list of prescriptions, allergies, supplements, and dose changes can prevent confusion across clinics, hospitals, pharmacies, and virtual consultations.</p>',
    'strong-telemedicine-visit' => '<p>A high-quality telemedicine visit should feel prepared, focused, and clinically responsible from both sides of the screen. The technology is only premium when it supports clear history, appropriate limits, careful documentation, and a safe follow-up plan.</p>',
    'reading-lab-reports-without-panic' => '<p>Lab reports can look absolute, but clinical interpretation is never just one red flag or one number. A calm, structured review helps patients understand what the test was meant to answer, what changed over time, and what should be discussed with a clinician.</p>',
    'home-metrics-blood-pressure-sugar-weight' => '<p>Home health metrics are most useful when they are measured consistently and interpreted as trends. Blood pressure, blood sugar, and weight can reveal important patterns between visits, but only when the numbers are recorded with context.</p>',
    'hospital-operations-smarter-beds-inventory' => '<p>Excellent hospital care depends on more than skilled clinicians. Bed visibility, inventory readiness, department coordination, and doctor assignment workflows all influence how quickly and safely a patient moves through the system.</p>',
    'mental-health-same-care-journey' => '<p>Whole-person healthcare cannot separate mental health from physical health. Stress, sleep, mood, trauma, chronic illness, and medication adherence often influence one another, so the care journey should make these conversations easier to start.</p>',
    'care-transitions-after-hospital-visit' => '<p>The riskiest part of care often begins after the hospital visit ends. Discharge instructions, medication changes, pending results, and follow-up plans must travel with the patient so the next clinician can continue care without dangerous gaps.</p>',
];

$editorialDepthByCategory = [
    'Care coordination' => <<<'HTML'
<h2>How mature care networks use command visibility</h2>
<p>A command center is not simply a dashboard. It is an operating discipline that helps teams see where the patient is in the journey, what has already happened, and what still needs attention. The best systems combine clinical context with operational status so appointments, report reviews, hospital requests, and follow-up tasks do not drift apart.</p>
<ul>
    <li>Keep one shared timeline for appointments, reports, prescriptions, and hospital activity.</li>
    <li>Separate urgent clinical signals from routine administrative tasks so teams can prioritize safely.</li>
    <li>Use role-based access so every professional sees enough context to act, without unnecessary exposure.</li>
    <li>Make follow-up ownership explicit after every major clinical event.</li>
</ul>
<h2>What excellent execution looks like</h2>
<p>Patients should never feel they are carrying the platform on their shoulders. A premium command-center experience makes the next step obvious, keeps authorized providers aligned, and reduces repeated explanations. The measurable outcome is not more screen activity; it is fewer missed handoffs and a calmer path through care.</p>
HTML,
    'Privacy' => <<<'HTML'
<h2>The consent standard patients can actually understand</h2>
<p>Strong privacy design uses plain language, clear time limits, and visible access choices. Patients should know who can view a record, why access is being requested, and how long that access will last. When consent is buried in complex language, the system may be compliant on paper but still feel unsafe to the person using it.</p>
<ul>
    <li>Explain the purpose of access before a record is shared.</li>
    <li>Show the patient which doctor, hospital, or care team is requesting permission.</li>
    <li>Allow consent to be reviewed, updated, or revoked where policy permits.</li>
    <li>Maintain audit trails so sensitive activity can be investigated responsibly.</li>
</ul>
<h2>Why privacy improves care quality</h2>
<p>Patients disclose more accurate information when they trust the system. That can affect medication history, mental health concerns, reproductive details, family risks, and financial barriers. Privacy is therefore not only a security feature; it is a clinical quality feature that supports better conversations and safer decisions.</p>
HTML,
    'Telemedicine' => <<<'HTML'
<h2>A premium virtual visit needs preparation</h2>
<p>The strongest video consultations begin before the call opens. Patients should be guided to prepare symptoms, medications, recent reports, and specific questions. Clinicians should be able to review relevant context quickly and decide whether video is appropriate or whether the case requires in-person assessment.</p>
<ul>
    <li>Confirm identity, location, and emergency backup details at the start of the visit.</li>
    <li>Review uploaded reports and current medications before giving next-step guidance.</li>
    <li>Document limitations of the remote exam when a physical assessment is needed.</li>
    <li>End with a written plan, warning signs, and follow-up timing.</li>
</ul>
<h2>Where virtual care creates real value</h2>
<p>Telemedicine is especially useful for follow-up, chronic disease review, report discussion, medication questions, and second opinions. It should not be oversold as a replacement for every visit. A premium platform earns trust by making remote care convenient while still directing patients to urgent or hands-on care when needed.</p>
HTML,
    'AI scanner' => <<<'HTML'
<h2>Guardrails that make AI safer</h2>
<p>AI-supported screening should collect structured symptoms, ask clarifying questions, and identify risk signals that deserve professional attention. It should avoid final diagnoses, exaggerated certainty, and recommendations that could delay emergency care. The safest AI output is specific enough to prepare the clinician but humble enough to remain supervised.</p>
<ul>
    <li>Label AI output as support, not diagnosis.</li>
    <li>Prioritize red-flag symptoms and escalation instructions.</li>
    <li>Show what information is missing so patients can complete the clinical picture.</li>
    <li>Keep clinicians accountable for interpretation and treatment decisions.</li>
</ul>
<h2>What teams should measure</h2>
<p>Good AI workflows can be evaluated by how often they reduce incomplete intake, improve routing, and shorten time to review for urgent cases. The goal is not to produce dramatic predictions. The goal is to make clinical work clearer, safer, and less burdened by disorganized information.</p>
HTML,
    'Digital care' => <<<'HTML'
<h2>The intake standard for safer first visits</h2>
<p>Digital intake should collect the details that influence decisions, not simply reproduce paper forms. A strong flow asks when symptoms started, what changed, what medicines are being used, which reports are relevant, and whether any warning signs require urgent attention before a routine booking.</p>
<ul>
    <li>Capture symptom timeline, severity, triggers, and associated warning signs.</li>
    <li>Collect allergies, active medications, supplements, and recent dose changes.</li>
    <li>Prompt patients to upload reports, prescriptions, imaging notes, or discharge summaries.</li>
    <li>Convert the intake into a concise clinician-facing summary.</li>
</ul>
<h2>How patients benefit immediately</h2>
<p>Prepared intake reduces repetition and helps patients feel taken seriously from the start. It also gives clinicians a cleaner first view, which can improve triage, appointment focus, and post-visit documentation. In a premium platform, intake should feel like care preparation, not administration.</p>
HTML,
    'Patient records' => <<<'HTML'
<h2>Build a record set that is actually useful</h2>
<p>The most useful record file is current, labeled, and selective. Families do not need to upload every historical document at once. They need the information that affects decisions today: active conditions, medicines, allergies, recent reports, procedures, hospital summaries, and emergency contacts.</p>
<ul>
    <li>Create one profile for each family member and review it monthly.</li>
    <li>Label files with date, test type, and the doctor or facility involved.</li>
    <li>Keep the newest report visible instead of burying it under duplicates.</li>
    <li>Use consent settings carefully when a caregiver needs access.</li>
</ul>
<h2>Why organization changes outcomes</h2>
<p>Good records shorten the distance between concern and action. A clinician can compare trends, avoid duplicated tests, understand past treatment, and notice medication risks sooner. The patient still receives professional care, but the conversation begins with clarity instead of guesswork.</p>
HTML,
    'Care access' => <<<'HTML'
<h2>A smarter way to compare care options</h2>
<p>Care discovery should help patients compare both clinical and practical factors. Specialty fit, availability, consultation mode, location, hospital access, fee expectations, and follow-up convenience all matter. A premium interface turns those inputs into a clear next step rather than a crowded directory.</p>
<ul>
    <li>Start with the type of care needed: routine, specialist, pharmacy, urgent, or emergency.</li>
    <li>Compare distance and availability with the seriousness of the symptoms.</li>
    <li>Check hospital services and departments when a condition may need escalation.</li>
    <li>Use emergency guidance immediately for severe or life-threatening symptoms.</li>
</ul>
<h2>What the patient should feel</h2>
<p>The experience should reduce panic. Patients should feel that the system understands urgency, guides them toward the right setting, and gives enough information to act confidently. That is the difference between a map and a care-navigation product.</p>
HTML,
    'Medication safety' => <<<'HTML'
<h2>How to maintain one reliable medication list</h2>
<p>A medication list should be treated like a living safety document. It needs medicine name, dose, timing, reason for use, prescribing professional, start date, stop date if relevant, and any side effects or allergies. Over-the-counter medicines and supplements belong on the same list because they can still interact with prescriptions.</p>
<ul>
    <li>Update the list after every appointment, hospital visit, or pharmacy change.</li>
    <li>Record why a medicine was started, stopped, or changed.</li>
    <li>Flag allergies and serious reactions where every clinician can see them.</li>
    <li>Bring the same list to telemedicine and in-person consultations.</li>
</ul>
<h2>The highest-risk moments</h2>
<p>Medication errors often happen during transitions: discharge, specialist referral, emergency treatment, or care in a new city. A connected list helps clinicians reconcile changes and prevents the patient from becoming the only source of truth when details are complex.</p>
HTML,
    'Reports' => <<<'HTML'
<h2>A calmer framework for reviewing results</h2>
<p>Patients should start with the reason the test was ordered, then compare the result to symptoms, medicines, recent illness, and previous values. Many abnormal flags are mild, temporary, or expected in context. Others need careful follow-up. The goal is not to ignore flags, but to interpret them with discipline.</p>
<ul>
    <li>Read the full report, not only highlighted numbers.</li>
    <li>Compare current values with older results when available.</li>
    <li>Write down symptoms, medicines, supplements, and recent infections before review.</li>
    <li>Ask the clinician what should be repeated, monitored, or treated.</li>
</ul>
<h2>How digital records help</h2>
<p>When reports stay attached to the care journey, doctors can review them beside the patient story instead of relying on memory or screenshots. That makes trend review easier and helps patients move from fear to informed preparation.</p>
HTML,
    'Preventive health' => <<<'HTML'
<h2>Turn home numbers into usable trends</h2>
<p>Home measurements should be recorded with timing and conditions. Blood pressure changes with rest, stress, caffeine, pain, and cuff placement. Blood sugar changes with meals, medication, sleep, illness, and activity. Weight changes with fluid balance and routine. Context turns raw numbers into clinical information.</p>
<ul>
    <li>Measure at consistent times and record the date, time, and relevant context.</li>
    <li>Use the same device when possible and follow the device instructions carefully.</li>
    <li>Share trends with the clinician instead of reacting to every single reading.</li>
    <li>Ask what values require urgent contact or emergency care.</li>
</ul>
<h2>Preventive care is pattern recognition</h2>
<p>A premium health platform should help patients see progress without creating anxiety. The right view highlights meaningful changes, supports follow-up, and keeps the clinician involved when readings move outside the expected range.</p>
HTML,
    'Hospital management' => <<<'HTML'
<h2>Operations are part of the patient experience</h2>
<p>Patients may never see bed dashboards, inventory ledgers, or assignment queues, but they feel the impact immediately. Delays, missing supplies, repeated calls, and unclear department ownership can make care feel chaotic. Strong operational tools reduce those hidden points of friction.</p>
<ul>
    <li>Track bed status in a way that supports admissions, transfers, and discharge planning.</li>
    <li>Monitor supplies before shortages affect clinical decisions.</li>
    <li>Connect doctor assignments with departments, requests, and patient context.</li>
    <li>Use dashboards to clarify work, not add extra administrative burden.</li>
</ul>
<h2>What administrators should protect</h2>
<p>The best hospital systems protect staff attention. They should surface exceptions, clarify ownership, and make patient movement easier to coordinate. Operational excellence is quiet when it works, but patients experience it as speed, confidence, and fewer avoidable delays.</p>
HTML,
    'Mental health' => <<<'HTML'
<h2>Make mental health easier to raise early</h2>
<p>Patients often mention sleep, stress, panic, grief, or low mood only after physical symptoms have already become harder to manage. A whole-person platform should make these concerns normal to record and discuss, while still respecting privacy and safety.</p>
<ul>
    <li>Ask about sleep, mood, stress, substance use, and safety in plain language.</li>
    <li>Connect mental health notes with appointments only when consent and role access are appropriate.</li>
    <li>Escalate urgent safety concerns to emergency or crisis support immediately.</li>
    <li>Support follow-up reminders so patients do not disappear after the first conversation.</li>
</ul>
<h2>Why integration matters</h2>
<p>Mental health affects chronic disease management, pain, medication adherence, recovery, and family support. Integrating it into the same care journey does not turn every doctor into a specialist. It helps the system notice when a patient needs more support and guide them toward the right level of care.</p>
HTML,
    'Care continuity' => <<<'HTML'
<h2>The handoff checklist after discharge</h2>
<p>Patients should leave a hospital visit with a clear understanding of what changed and what happens next. The care record should include diagnoses discussed, medicines started or stopped, tests still pending, warning signs, follow-up appointments, and the professional responsible for review.</p>
<ul>
    <li>Confirm the final medication list before leaving the hospital.</li>
    <li>Upload discharge summaries, prescriptions, reports, and procedure notes.</li>
    <li>Schedule follow-up within the recommended window.</li>
    <li>Write down symptoms that require urgent return or emergency contact.</li>
</ul>
<h2>How continuity prevents avoidable gaps</h2>
<p>Care transitions fail when every team assumes someone else has the full story. A connected platform can reduce that risk by keeping the discharge plan, records, and next appointment in one place. Families still need guidance, but they are no longer forced to reconstruct care from scattered paperwork.</p>
HTML,
];

$inlineVisualsByCategory = [
    'Care coordination' => [
        ['src' => 'https://images.unsplash.com/photo-1584982751601-97dcc096659c?auto=format&fit=crop&w=1400&q=86', 'alt' => 'Connected hospital command center', 'caption' => 'Shared care visibility helps teams coordinate appointments, records, and follow-up with less friction.'],
        ['src' => 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?auto=format&fit=crop&w=1400&q=86', 'alt' => 'Care team reviewing digital patient workflow', 'caption' => 'A premium network feels calm when every authorized care team sees the right context at the right moment.'],
    ],
    'Privacy' => [
        ['src' => 'https://images.unsplash.com/photo-1550831107-1553da8c8464?auto=format&fit=crop&w=1400&q=86', 'alt' => 'Clinician reviewing secure health data', 'caption' => 'Consent-led design gives patients confidence before sensitive records move between care teams.'],
        ['src' => 'https://images.unsplash.com/photo-1579154204601-01588f351e67?auto=format&fit=crop&w=1400&q=86', 'alt' => 'Healthcare privacy workflow on a tablet', 'caption' => 'Clear permissions and audit-aware access turn privacy from a checkbox into a trust experience.'],
    ],
    'Telemedicine' => [
        ['src' => 'https://images.unsplash.com/photo-1584515933487-779824d29309?auto=format&fit=crop&w=1400&q=86', 'alt' => 'Virtual care consultation with a specialist', 'caption' => 'Virtual rooms work best when video, reports, history, and follow-up live in one care journey.'],
        ['src' => 'https://images.unsplash.com/photo-1576091160550-2173dba999ef?auto=format&fit=crop&w=1400&q=86', 'alt' => 'Patient digital consultation preparation', 'caption' => 'Prepared patients and focused clinicians make remote consultations feel professional, safe, and complete.'],
    ],
    'AI scanner' => [
        ['src' => 'https://images.unsplash.com/photo-1582719471384-894fbb16e074?auto=format&fit=crop&w=1400&q=86', 'alt' => 'Clinical AI lab review environment', 'caption' => 'AI-supported triage is strongest when it organizes urgency while clinicians remain in control.'],
        ['src' => 'https://images.unsplash.com/photo-1579154204601-01588f351e67?auto=format&fit=crop&w=1400&q=86', 'alt' => 'Medical dashboard for report prioritization', 'caption' => 'Structured queues help care teams turn scattered patient signals into faster review decisions.'],
    ],
    'Digital care' => [
        ['src' => 'https://images.unsplash.com/photo-1576091160550-2173dba999ef?auto=format&fit=crop&w=1400&q=86', 'alt' => 'Doctor using a digital intake system', 'caption' => 'Good digital intake gives clinicians a cleaner first picture before the consultation begins.'],
        ['src' => 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?auto=format&fit=crop&w=1400&q=86', 'alt' => 'Patient data prepared for clinical review', 'caption' => 'When symptoms, reports, and history are prepared early, the appointment starts with more clarity.'],
    ],
    'Patient records' => [
        ['src' => 'https://images.unsplash.com/photo-1579684385127-1ef15d508118?auto=format&fit=crop&w=1400&q=86', 'alt' => 'Organized patient records and clinical notes', 'caption' => 'A current record set helps families hand doctors the facts that can change care decisions.'],
        ['src' => 'https://images.unsplash.com/photo-1588776814546-1ffcf47267a5?auto=format&fit=crop&w=1400&q=86', 'alt' => 'Medical documents prepared for an appointment', 'caption' => 'Record readiness is quiet patient safety: less searching, fewer missing details, and better handoffs.'],
    ],
    'Care access' => [
        ['src' => 'https://images.unsplash.com/photo-1586773860418-d37222d8fce3?auto=format&fit=crop&w=1400&q=86', 'alt' => 'Modern hospital access point', 'caption' => 'Location-aware care helps patients compare the right doors before worry turns into delay.'],
        ['src' => 'https://images.unsplash.com/photo-1538108149393-fbbd81895907?auto=format&fit=crop&w=1400&q=86', 'alt' => 'Healthcare facility corridor for patient access', 'caption' => 'Premium discovery balances clinical fit, practical travel, and urgency in one decision path.'],
    ],
    'Medication safety' => [
        ['src' => 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?auto=format&fit=crop&w=1400&q=86', 'alt' => 'Medication safety review with prescriptions', 'caption' => 'One shared medication list helps prevent missed allergies, duplicate treatments, and dose confusion.'],
        ['src' => 'https://images.unsplash.com/photo-1471864190281-a93a3070b6de?auto=format&fit=crop&w=1400&q=86', 'alt' => 'Pharmacy medication bottles and safety labels', 'caption' => 'Medication safety improves when prescriptions, supplements, and changes stay visible across visits.'],
    ],
    'Reports' => [
        ['src' => 'https://images.unsplash.com/photo-1631815588090-d4bfec5b1ccb?auto=format&fit=crop&w=1400&q=86', 'alt' => 'Laboratory report review', 'caption' => 'Lab results become less overwhelming when trends, symptoms, and clinician review are kept together.'],
        ['src' => 'https://images.unsplash.com/photo-1576671081837-49000212a370?auto=format&fit=crop&w=1400&q=86', 'alt' => 'Clinical lab testing workflow', 'caption' => 'A report is most useful when it answers a clear clinical question and fits the wider patient story.'],
    ],
    'Preventive health' => [
        ['src' => 'https://images.unsplash.com/photo-1532938911079-1b06ac7ceec7?auto=format&fit=crop&w=1400&q=86', 'alt' => 'Preventive health check with vital signs', 'caption' => 'Home readings become meaningful when they are consistent, contextual, and easy to share.'],
        ['src' => 'https://images.unsplash.com/photo-1505751172876-fa1923c5c528?auto=format&fit=crop&w=1400&q=86', 'alt' => 'Preventive care equipment and monitoring tools', 'caption' => 'Preventive care is not about perfect numbers; it is about reliable patterns that guide better follow-up.'],
    ],
    'Hospital management' => [
        ['src' => 'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?auto=format&fit=crop&w=1400&q=86', 'alt' => 'Hospital ward operations and bed readiness', 'caption' => 'Operational visibility reduces delays before they become patient experience problems.'],
        ['src' => 'https://images.unsplash.com/photo-1581056771107-24ca5f033842?auto=format&fit=crop&w=1400&q=86', 'alt' => 'Hospital care team coordination', 'caption' => 'Beds, departments, inventory, and doctor assignment work better when they share one operational picture.'],
    ],
    'Mental health' => [
        ['src' => 'https://images.unsplash.com/photo-1573497620053-ea5300f94f21?auto=format&fit=crop&w=1400&q=86', 'alt' => 'Mental health consultation in a calm setting', 'caption' => 'Whole-person care makes room for stress, sleep, mood, and safety alongside physical symptoms.'],
        ['src' => 'https://images.unsplash.com/photo-1559757148-5c350d0d3c56?auto=format&fit=crop&w=1400&q=86', 'alt' => 'Supportive healthcare conversation', 'caption' => 'Digital access can lower the barrier to starting difficult conversations early.'],
    ],
    'Care continuity' => [
        ['src' => 'https://images.unsplash.com/photo-1576765607924-3f7b8410a787?auto=format&fit=crop&w=1400&q=86', 'alt' => 'Care team planning a hospital follow-up', 'caption' => 'Discharge details, medicines, and warning signs should travel with the patient into follow-up care.'],
        ['src' => 'https://images.unsplash.com/photo-1581056771107-24ca5f033842?auto=format&fit=crop&w=1400&q=86', 'alt' => 'Clinical handoff between hospital care teams', 'caption' => 'Continuity feels premium when the next clinician does not have to reconstruct the story from memory.'],
    ],
];

$fallbackInlineVisuals = [
    ['src' => 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?auto=format&fit=crop&w=1400&q=86', 'alt' => 'Connected healthcare platform workflow', 'caption' => 'Connected care keeps the patient story ready for the next safe decision.'],
    ['src' => 'https://images.unsplash.com/photo-1584982751601-97dcc096659c?auto=format&fit=crop&w=1400&q=86', 'alt' => 'Modern healthcare coordination environment', 'caption' => 'A premium healthcare network should feel organized from discovery to follow-up.'],
];

$articleInlineVisuals = $inlineVisualsByCategory[(string) ($article['category'] ?? '')] ?? $fallbackInlineVisuals;

$renderInlineFigure = static function (array $visual): string {
    $src = (string) ($visual['src'] ?? '');
    if ($src === '') {
        return '';
    }

    $alt = (string) ($visual['alt'] ?? 'Healthcare article visual');
    $caption = (string) ($visual['caption'] ?? 'Connected care insight');

    return '<figure class="health-article-inline-media">'
        . '<img src="' . e($src) . '" alt="' . e($alt) . '" loading="lazy">'
        . '<figcaption>' . e($caption) . '</figcaption>'
        . '</figure>';
};

$injectInlineVisuals = static function (string $content, array $visuals, callable $renderFigure): string {
    if (trim($content) === '' || empty($visuals)) {
        return $content;
    }

    preg_match_all('/<p[\s>]/i', $content, $paragraphMatches);
    $paragraphTotal = count($paragraphMatches[0]);
    if ($paragraphTotal < 2) {
        return $content;
    }

    $slots = [2];
    if (count($visuals) > 1 && $paragraphTotal >= 4) {
        $slots[] = max(3, $paragraphTotal - 1);
    } elseif (count($visuals) > 1 && $paragraphTotal >= 3) {
        $slots[] = 3;
    }
    $slots = array_values(array_unique($slots));

    $parts = preg_split('/(<\/p>)/i', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
    if (!$parts) {
        return $content;
    }

    $enhanced = '';
    $paragraphIndex = 0;
    $visualIndex = 0;
    $partCount = count($parts);

    for ($index = 0; $index < $partCount; $index += 2) {
        $chunk = $parts[$index] ?? '';
        $closing = $parts[$index + 1] ?? '';
        $enhanced .= $chunk . $closing;

        if ($closing !== '' && preg_match('/<p[\s>]/i', $chunk)) {
            $paragraphIndex++;
            if (in_array($paragraphIndex, $slots, true) && isset($visuals[$visualIndex])) {
                $enhanced .= $renderFigure($visuals[$visualIndex]);
                $visualIndex++;
            }
        }
    }

    return $enhanced;
};

$applyEditorialEnhancement = static function (string $content, array $article) use ($editorialOpeningBySlug, $editorialDepthByCategory): string {
    $slug = (string) ($article['slug'] ?? '');
    $category = (string) ($article['category'] ?? '');
    $opening = $editorialOpeningBySlug[$slug] ?? '';
    $depth = trim($editorialDepthByCategory[$category] ?? '');

    if ($opening !== '') {
        $updatedContent = preg_replace('/<p\b[^>]*>.*?<\/p>/is', $opening, $content, 1);
        if (is_string($updatedContent)) {
            $content = $updatedContent;
        }
    }

    if ($depth !== '') {
        $updatedContent = preg_replace_callback(
            '/<div class="health-disclaimer">/i',
            static fn(array $matches): string => "\n" . $depth . "\n\n" . $matches[0],
            $content,
            1,
            $disclaimerCount
        );

        if (is_string($updatedContent) && $disclaimerCount > 0) {
            $content = $updatedContent;
        } else {
            $content .= "\n\n" . $depth;
        }
    }

    return $content;
};

$enhancedArticleContent = $applyEditorialEnhancement((string) $article['content'], $article);
$articleContent = $injectInlineVisuals($enhancedArticleContent, $articleInlineVisuals, $renderInlineFigure);
$readingTime = max(1, (int) ceil(str_word_count(strip_tags($articleContent)) / 180));
?>

<article class="health-article-shell">
    <section class="health-article-hero health-article-hero-premium" data-aos="fade-up">
        <?php if ($isVideo && !$isLocked): ?>
            <div class="health-article-video <?= $voiceoverUrl ? 'has-voiceover' : '' ?>" data-voice-player>
                <video autoplay muted loop playsinline preload="auto" poster="<?= e($article['video_poster'] ?: $article['card_image']) ?>" data-voice-video>
                    <source src="<?= e(app_url($article['video_url'])) ?>" type="video/mp4">
                    Your browser does not support the video player.
                </video>
                <?php if ($voiceoverUrl): ?>
                    <button type="button" class="video-sound-toggle" data-voice-toggle aria-pressed="false" aria-label="Open video voice">
                        <i class="fa-solid fa-volume-xmark"></i>
                        <span>Voice Open</span>
                    </button>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <img src="<?= e($heroImage) ?>" alt="<?= e($article['title']) ?>" class="health-article-hero-image">
        <?php endif; ?>

        <div class="health-article-hero-overlay">
            <span class="blog-card-kicker"><?= e(ucfirst((string) $article['type'])) ?> &middot; <?= e($article['category']) ?> &middot; <?= e($isVideo ? 'Video feature' : 'Image feature') ?></span>
            <h1><?= e($article['title']) ?></h1>
            <p><?= e($article['excerpt']) ?></p>
            <div class="health-article-hero-meta">
                <span><i class="fa-regular fa-calendar"></i> <?= e($publishedDate) ?></span>
                <span><i class="fa-regular fa-clock"></i> <?= (int) ($readingTime ?? 1) ?> min read</span>
                <?php if ($isLocked): ?><span><i class="fa-solid fa-crown"></i> <?= e($requiredLevel) ?> access</span><?php endif; ?>
            </div>
            <?php if ($isLocked): ?>
                <button type="button" class="btn btn-primary health-article-unlock" data-premium-locked="1">
                    <i class="fa-solid fa-lock"></i>
                    Unlock <?= e($requiredLevel) ?> Article
                </button>
            <?php endif; ?>
        </div>
    </section>

    <section class="health-author-row" data-aos="fade-up">
        <img src="<?= e($article['author_avatar']) ?>" alt="<?= e($article['author_name']) ?>" class="health-author-avatar">
        <div class="health-author-copy">
            <strong><?= e($article['author_name']) ?></strong>
            <span><?= e($article['author_designation']) ?></span>
        </div>
        <div class="health-article-author-label">
            <span>Specialist insight</span>
        </div>
    </section>

    <?php if ($isLocked): ?>
        <section class="premium-inline-banner" data-aos="fade-up">
            <div>
                <span class="eyebrow"><?= e($requiredLevel) ?> article</span>
                <h2>The article layout is visible. Full reading and video playback unlock with subscription.</h2>
                <p><?= e($article['excerpt']) ?></p>
            </div>
            <button class="btn btn-primary" type="button" data-premium-locked="1">
                <i class="fa-solid fa-crown"></i>
                Unlock Article
            </button>
        </section>
    <?php else: ?>
        <section class="glass-panel health-article-content" data-aos="fade-up">
            <?= $articleContent ?>
        </section>
    <?php endif; ?>
</article>

<?php if (!empty($relatedArticles)): ?>
    <section class="health-related-section" data-aos="fade-up">
        <div class="blog-section-head">
            <div>
                <span class="eyebrow">More from the health library</span>
                <h2>Related health stories</h2>
            </div>
            <a href="<?= route_url('blog') ?>" class="btn btn-outline-primary btn-sm">View all articles</a>
        </div>
        <div class="blog-card-grid">
            <?php foreach ($relatedArticles as $related): ?>
                <?php
                $relatedIsVideo = ($related['media_type'] ?? '') === 'video';
                $relatedImage = $relatedIsVideo
                    ? ($related['video_poster'] ?: $related['card_image'])
                    : ($related['card_image'] ?: $related['hero_image']);
                ?>
                <a href="<?= route_url('article', ['slug' => $related['slug']]) ?>" class="blog-story-card blog-dynamic-card <?= $relatedIsVideo ? 'is-video' : 'is-image' ?> <?= !empty($related['is_locked']) ? 'is-premium-locked' : '' ?>">
                    <div class="blog-card-media">
                        <?php if ($relatedIsVideo && !empty($related['video_url'])): ?>
                            <video autoplay muted loop playsinline preload="metadata" poster="<?= e($relatedImage) ?>" aria-label="<?= e($related['title']) ?> video preview">
                                <source src="<?= e(app_url($related['video_url'])) ?>" type="video/mp4">
                            </video>
                        <?php else: ?>
                            <img src="<?= e($relatedImage) ?>" alt="<?= e($related['title']) ?>" loading="lazy">
                        <?php endif; ?>
                        <span class="blog-media-badge"><i class="fa-solid <?= $relatedIsVideo ? 'fa-circle-play' : 'fa-image' ?>"></i> <?= $relatedIsVideo ? 'Video story' : 'Image story' ?></span>
                        <?php if (!empty($related['is_premium'])): ?>
                            <span class="blog-premium-badge"><i class="fa-solid fa-crown"></i> Premium</span>
                        <?php endif; ?>
                        <?php if ($relatedIsVideo && !empty($related['voiceover_url'])): ?>
                            <span class="blog-voice-badge"><i class="fa-solid fa-volume-high"></i> Voice</span>
                        <?php endif; ?>
                    </div>
                    <div class="blog-story-body">
                        <span class="blog-card-kicker"><?= e(ucfirst((string) $related['type'])) ?> &middot; <?= e($related['category']) ?></span>
                        <h3><?= e($related['title']) ?></h3>
                        <p><?= e($related['excerpt']) ?></p>
                        <div class="blog-card-author">
                            <img src="<?= e($related['author_avatar']) ?>" alt="<?= e($related['author_name']) ?>" loading="lazy">
                            <span><strong><?= e($related['author_name']) ?></strong><small><?= e($related['author_designation']) ?></small></span>
                        </div>
                        <div class="blog-story-meta">
                            <span><?= e(date('M j, Y', strtotime((string) $related['published_at']))) ?></span>
                            <span class="blog-story-read">Read <i class="fa-solid fa-arrow-right"></i></span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
