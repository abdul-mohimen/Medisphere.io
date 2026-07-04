<?php
namespace App\Models;

class DiseaseInformation extends BaseModel
{
    public function all(string $status = 'published'): array
    {
        if ($status === 'all') {
            return $this->db->query('SELECT * FROM disease_information ORDER BY disease_name ASC')->fetchAll();
        }

        $stmt = $this->db->prepare('SELECT * FROM disease_information WHERE status = :status ORDER BY disease_name ASC');
        $stmt->execute(['status' => $status]);
        return $this->mergeBuiltInCatalog($stmt->fetchAll(), $status);
    }

    public function search(string $query): array
    {
        if ($this->isDiseaseIntent($query)) {
            return $this->all('published');
        }

        $stmt = $this->db->prepare('SELECT * FROM disease_information
            WHERE status = "published"
            AND (
                disease_name LIKE :query_name
                OR overview LIKE :query_overview
                OR symptoms LIKE :query_symptoms
                OR causes LIKE :query_causes
                OR prevention LIKE :query_prevention
                OR treatment LIKE :query_treatment
                OR emergency_notes LIKE :query_emergency_notes
                OR seo_title LIKE :query_seo_title
                OR seo_description LIKE :query_seo_description
            )
            ORDER BY disease_name ASC');
        $term = '%' . $query . '%';
        $stmt->execute([
            'query_name' => $term,
            'query_overview' => $term,
            'query_symptoms' => $term,
            'query_causes' => $term,
            'query_prevention' => $term,
            'query_treatment' => $term,
            'query_emergency_notes' => $term,
            'query_seo_title' => $term,
            'query_seo_description' => $term,
        ]);

        $matches = $this->enrichRowsWithCatalogDefaults($stmt->fetchAll());
        $catalogMatches = array_values(array_filter($this->builtInCatalog(), fn(array $item): bool => $this->matchesCatalogQuery($item, $query)));
        return $this->sortByName($this->mergeRowsBySlug(array_merge($catalogMatches, $matches)));
    }

    public function related(int $id, int $limit = 4): array
    {
        $all = $this->all('published');
        $current = null;
        foreach ($all as $row) {
            if ((int) ($row['id'] ?? 0) === $id) {
                $current = $row;
                break;
            }
        }

        $category = $current['taxonomy_category'] ?? null;
        $related = array_values(array_filter($all, static function (array $row) use ($id, $category): bool {
            if ((int) ($row['id'] ?? 0) === $id) {
                return false;
            }

            return !$category || ($row['taxonomy_category'] ?? null) === $category;
        }));

        if (count($related) < $limit) {
            foreach ($all as $row) {
                if ((int) ($row['id'] ?? 0) === $id) {
                    continue;
                }
                $related[$row['slug']] = $row;
            }
            $related = array_values($related);
        }

        return array_slice($related, 0, $limit);
    }

    public function countPublished(): int
    {
        return count($this->all('published'));
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM disease_information WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if ($row) {
            return $row;
        }

        foreach ($this->builtInCatalog() as $catalogRow) {
            if ((int) $catalogRow['id'] === $id) {
                return $catalogRow;
            }
        }

        return null;
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM disease_information WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();
        if ($row) {
            return $row;
        }

        foreach ($this->builtInCatalog() as $catalogRow) {
            if ($catalogRow['slug'] === $slug) {
                return $catalogRow;
            }
        }

        return null;
    }

    public function save(array $data): int
    {
        $payload = [
            'slug' => $data['slug'],
            'disease_name' => $data['disease_name'],
            'overview' => $data['overview'] ?? null,
            'symptoms' => $data['symptoms'] ?? null,
            'causes' => $data['causes'] ?? null,
            'prevention' => $data['prevention'] ?? null,
            'treatment' => $data['treatment'] ?? null,
            'emergency_notes' => $data['emergency_notes'] ?? null,
            'status' => $data['status'] ?? 'published',
            'featured_image_path' => $data['featured_image_path'] ?? null,
            'seo_title' => $data['seo_title'] ?? null,
            'seo_description' => $data['seo_description'] ?? null,
        ];

        if (!empty($data['id'])) {
            $stmt = $this->db->prepare('UPDATE disease_information SET slug = :slug, disease_name = :disease_name, overview = :overview, symptoms = :symptoms, causes = :causes, prevention = :prevention, treatment = :treatment, emergency_notes = :emergency_notes, status = :status, featured_image_path = :featured_image_path, seo_title = :seo_title, seo_description = :seo_description, updated_at = NOW() WHERE id = :id');
            $stmt->execute(array_merge($payload, ['id' => $data['id']]));
            return (int) $data['id'];
        }

        $stmt = $this->db->prepare('INSERT INTO disease_information (slug, disease_name, overview, symptoms, causes, prevention, treatment, emergency_notes, status, featured_image_path, seo_title, seo_description, created_at, updated_at) VALUES (:slug, :disease_name, :overview, :symptoms, :causes, :prevention, :treatment, :emergency_notes, :status, :featured_image_path, :seo_title, :seo_description, NOW(), NOW())');
        $stmt->execute($payload);
        return (int) $this->db->lastInsertId();
    }

    private function mergeBuiltInCatalog(array $databaseRows, string $status): array
    {
        if ($status !== 'published') {
            return $databaseRows;
        }

        return $this->sortByName($this->mergeRowsBySlug(array_merge($this->builtInCatalog(), $databaseRows)));
    }

    private function mergeRowsBySlug(array $rows): array
    {
        $merged = [];
        foreach ($rows as $row) {
            $slug = (string) ($row['slug'] ?? '');
            if ($slug === '') {
                continue;
            }
            $merged[$slug] = isset($merged[$slug]) ? array_merge($merged[$slug], $row) : $row;
        }

        return array_values($merged);
    }

    private function enrichRowsWithCatalogDefaults(array $rows): array
    {
        $catalog = [];
        foreach ($this->builtInCatalog() as $item) {
            $catalog[$item['slug']] = $item;
        }

        return array_map(static function (array $row) use ($catalog): array {
            $slug = (string) ($row['slug'] ?? '');
            return isset($catalog[$slug]) ? array_merge($catalog[$slug], $row) : $row;
        }, $rows);
    }

    private function sortByName(array $rows): array
    {
        usort($rows, static fn(array $a, array $b): int => strcmp((string) ($a['disease_name'] ?? ''), (string) ($b['disease_name'] ?? '')));
        return $rows;
    }

    private function matchesCatalogQuery(array $item, string $query): bool
    {
        $term = strtolower(trim($query));
        if ($term === '') {
            return true;
        }

        $haystack = strtolower(strip_tags(implode(' ', [
            $item['disease_name'] ?? '',
            $item['overview'] ?? '',
            $item['symptoms'] ?? '',
            $item['causes'] ?? '',
            $item['prevention'] ?? '',
            $item['treatment'] ?? '',
            $item['emergency_notes'] ?? '',
            $item['seo_title'] ?? '',
            $item['seo_description'] ?? '',
            $item['taxonomy_category'] ?? '',
            $item['condition_type'] ?? '',
            $item['pathogen_type'] ?? '',
            implode(' ', $item['diagnostic_tests'] ?? []),
            implode(' ', $item['risk_tags'] ?? []),
        ])));

        return str_contains($haystack, $term);
    }

    private function isDiseaseIntent(string $query): bool
    {
        $normalized = strtolower(trim(preg_replace('/[^a-z0-9]+/i', ' ', $query) ?? ''));
        if ($normalized === '') {
            return false;
        }

        $diseaseWords = ['disease', 'diseases', 'diease', 'dieases', 'illness', 'illnesses', 'condition', 'conditions', 'pathogen', 'pathogens'];
        if (in_array($normalized, $diseaseWords, true)) {
            return true;
        }

        $tokens = array_values(array_filter(explode(' ', $normalized)));
        foreach ($tokens as $token) {
            foreach ($diseaseWords as $word) {
                if (levenshtein($token, $word) <= 2) {
                    return true;
                }
            }
        }

        return false;
    }

    private function builtInCatalog(): array
    {
        static $catalog = null;
        if ($catalog !== null) {
            return $catalog;
        }

        $rows = [
            $this->catalogItem('covid-19', 'COVID-19', 'Infectious Diseases', 'Viral respiratory pathogen', 'High', 'COVID-19 is a contagious respiratory illness caused by SARS-CoV-2. It can range from mild upper-respiratory symptoms to pneumonia, long COVID, and severe disease in high-risk patients.', 'Fever|Cough|Sore throat|Loss of smell or taste|Shortness of breath|Fatigue', 'SARS-CoV-2 spread through respiratory particles and close indoor exposure.', 'Stay current with recommended vaccination|Improve indoor ventilation|Use masks in high-risk settings|Stay home when sick', 'Supportive care, antivirals for eligible high-risk patients, oxygen and hospital care for severe illness.', 'Seek urgent care for breathing difficulty, blue lips, confusion, chest pain, severe weakness, or low oxygen readings.', ['Rapid antigen test', 'PCR or NAAT', 'Pulse oximetry', 'Chest imaging when severe'], ['Respiratory', 'Pandemic pathogen', 'Vaccination']),
            $this->catalogItem('influenza', 'Influenza', 'Infectious Diseases', 'Viral respiratory pathogen', 'Moderate', 'Influenza is a seasonal respiratory viral infection that can cause severe complications in infants, older adults, pregnant people, and patients with chronic disease.', 'Fever|Body aches|Dry cough|Headache|Sore throat|Sudden fatigue', 'Influenza A or B viruses spread through respiratory droplets and contaminated hands.', 'Annual flu vaccination|Hand hygiene|Avoid close contact when ill|Protect high-risk patients during seasonal peaks', 'Antivirals may help early in illness for eligible patients, with fluids, fever control, and monitoring.', 'Urgent care is needed for breathing difficulty, dehydration, confusion, chest pain, or symptoms improving then suddenly worsening.', ['Rapid influenza test', 'PCR panel', 'Clinical exam', 'Chest X-ray if pneumonia is suspected'], ['Respiratory', 'Seasonal', 'Vaccine-preventable']),
            $this->catalogItem('respiratory-syncytial-virus', 'Respiratory Syncytial Virus (RSV)', 'Infectious Diseases', 'Viral respiratory pathogen', 'Moderate', 'RSV commonly causes cold-like illness but can lead to bronchiolitis, pneumonia, and severe breathing problems in infants and older adults.', 'Runny nose|Cough|Wheezing|Poor feeding in infants|Fast breathing|Fever', 'RSV spreads through respiratory secretions, close contact, and contaminated surfaces.', 'Hand hygiene|Avoid exposing infants to sick contacts|Clean high-touch surfaces|Use preventive products when recommended for high-risk groups', 'Most care is supportive; severe cases may need oxygen, hydration support, or hospital monitoring.', 'Seek urgent care for pauses in breathing, blue lips, severe wheeze, dehydration, or a child struggling to breathe.', ['RSV antigen or PCR', 'Pulse oximetry', 'Respiratory exam', 'Chest imaging if complications are suspected'], ['Respiratory', 'Pediatrics', 'Older adults']),
            $this->catalogItem('tuberculosis', 'Tuberculosis', 'Infectious Diseases', 'Bacterial pathogen', 'High', 'Tuberculosis is a bacterial infection caused by Mycobacterium tuberculosis. It most often affects the lungs but can involve other organs.', 'Cough lasting weeks|Night sweats|Weight loss|Fever|Chest pain|Coughing blood', 'Airborne spread from a person with infectious pulmonary TB; risk rises with close exposure, HIV, undernutrition, or crowded settings.', 'Screen exposed contacts|Complete latent TB treatment when prescribed|Improve ventilation|Use respiratory precautions for active disease', 'Treatment uses multiple antibiotics for months. Completing the full regimen is essential to cure infection and reduce resistance.', 'Urgent care is needed for coughing blood, severe breathlessness, confusion, or inability to keep medicines down.', ['Sputum smear and culture', 'NAAT', 'Chest X-ray', 'Tuberculin or IGRA test', 'Drug-susceptibility testing'], ['Airborne', 'AMR watch', 'Global infectious killer']),
            $this->catalogItem('malaria', 'Malaria', 'Tropical & Vector-Borne', 'Parasitic vector-borne disease', 'High', 'Malaria is a mosquito-borne parasitic infection that can rapidly become life-threatening, especially with Plasmodium falciparum.', 'Fever|Chills|Sweats|Headache|Vomiting|Severe anemia|Confusion in severe cases', 'Plasmodium parasites transmitted by infected Anopheles mosquitoes.', 'Use insecticide-treated nets|Take travel prophylaxis when recommended|Remove mosquito breeding sites|Seek testing quickly after fever in endemic areas', 'Antimalarial treatment depends on species, severity, resistance patterns, pregnancy status, and local guidance.', 'Emergency care is required for confusion, seizures, jaundice, severe weakness, breathing difficulty, or dark urine.', ['Rapid diagnostic test', 'Thick and thin blood smear', 'CBC', 'Glucose and kidney tests'], ['Vector-borne', 'Parasitic', 'Travel medicine']),
            $this->catalogItem('dengue-fever', 'Dengue Fever', 'Tropical & Vector-Borne', 'Viral vector-borne disease', 'High', 'Dengue is a mosquito-borne viral illness that can progress to severe dengue with bleeding, plasma leakage, shock, or organ involvement.', 'High fever|Severe body aches|Headache behind eyes|Rash|Nausea|Bleeding gums or nose', 'Dengue virus spread by infected Aedes mosquitoes.', 'Remove standing water|Use repellents|Wear protective clothing|Use screens and community mosquito control', 'Treatment is supportive with hydration and monitoring. Avoid aspirin or ibuprofen unless a clinician advises otherwise.', 'Seek urgent care for severe belly pain, persistent vomiting, bleeding, fainting, drowsiness, cold hands or feet, or breathing difficulty.', ['NS1 antigen', 'Dengue PCR', 'IgM/IgG serology', 'CBC and hematocrit', 'Platelet count'], ['Vector-borne', 'Warning signs', 'Tropical']),
            $this->catalogItem('cholera', 'Cholera', 'Infectious Diseases', 'Bacterial waterborne disease', 'High', 'Cholera is an acute diarrheal infection that can cause rapid dehydration and death without prompt fluid replacement.', 'Watery diarrhea|Vomiting|Leg cramps|Severe thirst|Sunken eyes|Low urine', 'Toxigenic Vibrio cholerae from contaminated water or food.', 'Safe water|Sanitation|Hand hygiene|Food safety|Oral cholera vaccination in high-risk settings', 'Rapid oral rehydration is central. Severe dehydration needs IV fluids; antibiotics may be used in selected cases.', 'Emergency care is needed for lethargy, inability to drink, very low urine, fainting, or signs of shock.', ['Stool culture', 'Rapid diagnostic test', 'Electrolytes', 'Hydration assessment'], ['Waterborne', 'Outbreak', 'Rehydration']),
            $this->catalogItem('measles', 'Measles', 'Infectious Diseases', 'Viral vaccine-preventable disease', 'High', 'Measles is a highly contagious viral illness that can cause pneumonia, brain inflammation, and severe complications in undervaccinated communities.', 'High fever|Cough|Runny nose|Red eyes|Koplik spots|Widespread rash', 'Measles virus spreads through airborne respiratory particles and can linger in indoor air.', 'Two-dose measles vaccination|Isolation during infectious period|Rapid public health notification|Protect infants and immunocompromised people', 'Care is supportive; vitamin A may be used in some children. Complications need prompt treatment.', 'Urgent care is needed for breathing difficulty, confusion, dehydration, seizures, or severe lethargy.', ['Clinical exam', 'Measles IgM', 'PCR from throat/nasopharyngeal sample', 'Public health exposure assessment'], ['Airborne', 'Vaccine-preventable', 'Outbreak']),
            $this->catalogItem('mpox', 'Mpox', 'Infectious Diseases', 'Viral zoonotic disease', 'Moderate', 'Mpox is an orthopoxvirus infection that causes rash, systemic symptoms, and transmission through close contact.', 'Fever|Swollen lymph nodes|Rash or lesions|Body aches|Sore throat|Rectal pain in some cases', 'Mpox virus spread by close skin, respiratory, sexual, or contaminated-material contact.', 'Avoid close contact with lesions|Vaccination for eligible risk groups|Isolation until lesions heal|Use protective care practices', 'Treatment is often supportive; antivirals may be considered for severe disease or high-risk patients.', 'Seek urgent care for eye involvement, severe pain, dehydration, confusion, breathing problems, or rapidly worsening lesions.', ['PCR from lesion swab', 'Clinical exam', 'Exposure history', 'Testing for co-infections when relevant'], ['Zoonotic', 'Rash illness', 'Close contact']),
            $this->catalogItem('ebola-virus-disease', 'Ebola Virus Disease', 'High-Consequence Pathogens', 'Viral hemorrhagic fever', 'Critical', 'Ebola virus disease is a severe viral illness with high fatality risk that requires immediate isolation, specialist care, and public health response.', 'Fever|Severe weakness|Vomiting|Diarrhea|Abdominal pain|Bleeding in some cases', 'Ebolavirus exposure through infected body fluids, contaminated materials, or infected animals.', 'Rapid isolation|Protective equipment|Safe burial practices|Vaccination in eligible outbreak settings|Avoid contact with body fluids', 'Care requires specialist supportive treatment, fluid and electrolyte management, and approved therapeutics for specific outbreaks.', 'Any suspected exposure with fever is urgent. Call ahead and avoid public waiting areas.', ['PCR testing in specialist lab', 'CBC and chemistry', 'Exposure risk assessment', 'Public health notification'], ['Hemorrhagic fever', 'Outbreak', 'Isolation']),
            $this->catalogItem('marburg-virus-disease', 'Marburg Virus Disease', 'High-Consequence Pathogens', 'Viral hemorrhagic fever', 'Critical', 'Marburg virus disease is a rare but severe hemorrhagic fever requiring immediate infection-control precautions and specialist management.', 'High fever|Severe headache|Malaise|Vomiting|Diarrhea|Bleeding in severe cases', 'Marburg virus exposure through infected body fluids, contaminated materials, or reservoir bats.', 'Avoid contact with infected body fluids|Use protective equipment|Follow outbreak guidance|Safe handling and burial practices', 'Treatment is supportive with intensive fluid, electrolyte, and organ support while public health teams manage contacts.', 'Suspected exposure plus fever should be treated as an emergency requiring isolation and public health notification.', ['PCR in specialist lab', 'Exposure history', 'CBC and liver tests', 'Public health coordination'], ['Hemorrhagic fever', 'Outbreak', 'Isolation']),
            $this->catalogItem('lassa-fever', 'Lassa Fever', 'High-Consequence Pathogens', 'Viral hemorrhagic fever', 'High', 'Lassa fever is a viral illness found in parts of West Africa. It can cause severe disease, hearing loss, bleeding, and complications in pregnancy.', 'Fever|Weakness|Sore throat|Chest pain|Vomiting|Facial swelling|Bleeding in severe cases', 'Lassa virus exposure from infected rodent urine or droppings, or person-to-person body fluid spread.', 'Food and home rodent control|Safe waste handling|Protective care practices|Early testing during outbreaks', 'Supportive care and early antiviral treatment may be used under specialist guidance.', 'Urgent care is needed for bleeding, breathing difficulty, confusion, shock, pregnancy with suspected infection, or severe weakness.', ['PCR', 'Serology', 'CBC and liver tests', 'Exposure history'], ['Hemorrhagic fever', 'Rodent-borne', 'West Africa']),
            $this->catalogItem('nipah-virus-infection', 'Nipah Virus Infection', 'High-Consequence Pathogens', 'Viral zoonotic disease', 'Critical', 'Nipah virus infection can cause severe encephalitis and respiratory disease. Outbreaks require rapid isolation and public health response.', 'Fever|Headache|Drowsiness|Confusion|Seizures|Cough or breathing symptoms', 'Nipah virus exposure from infected bats, contaminated food, pigs, or close contact with infected people.', 'Avoid raw date palm sap in outbreak areas|Avoid sick animal exposure|Use infection-control precautions|Rapid contact tracing', 'Treatment is supportive and often requires intensive care for brain or respiratory complications.', 'Suspected exposure with fever, confusion, seizures, or breathing difficulty is an emergency.', ['PCR', 'Serology', 'Brain imaging when encephalitis suspected', 'Exposure history'], ['Encephalitis', 'Zoonotic', 'Outbreak']),
            $this->catalogItem('rabies', 'Rabies', 'High-Consequence Pathogens', 'Viral zoonotic disease', 'Critical', 'Rabies is a fatal viral infection once symptoms begin, but prompt wound care and post-exposure prophylaxis can prevent disease after exposure.', 'Tingling at bite site|Fever|Anxiety|Difficulty swallowing|Hydrophobia|Confusion|Paralysis', 'Rabies virus transmitted through saliva from infected animals, usually bites or scratches.', 'Immediate wound washing after bites|Post-exposure vaccination and immunoglobulin when indicated|Vaccinate pets|Avoid stray or wild animal contact', 'After exposure, urgent post-exposure prophylaxis is preventive. Symptomatic rabies requires critical supportive care.', 'Any possible rabies exposure should be assessed urgently the same day.', ['Exposure assessment', 'Animal testing when available', 'Specialist tests after symptoms begin'], ['Zoonotic', 'Fatal if symptomatic', 'Post-exposure prophylaxis']),
            $this->catalogItem('hiv-aids', 'HIV/AIDS', 'Infectious Diseases', 'Viral chronic infection', 'High', 'HIV is a chronic viral infection that weakens immunity if untreated. Effective antiretroviral therapy can suppress the virus and prevent progression.', 'Early fever or rash|Swollen glands|Weight loss|Night sweats|Frequent infections when advanced', 'Human immunodeficiency virus spread through blood, sexual fluids, and from parent to child without prevention.', 'Testing and early treatment|Condoms and safer sex|PrEP for eligible patients|Sterile needles|Prevention in pregnancy', 'Antiretroviral therapy is long-term and should be started and continued with clinician monitoring.', 'Urgent care is needed for severe infection, meningitis symptoms, confusion, breathing difficulty, or major weight loss.', ['HIV antigen/antibody test', 'HIV viral load', 'CD4 count', 'Resistance testing', 'Screening for co-infections'], ['Chronic infection', 'Immunology', 'Prevention']),
            $this->catalogItem('hepatitis-b', 'Hepatitis B', 'Infectious Diseases', 'Viral liver infection', 'Moderate', 'Hepatitis B can be acute or chronic and may lead to cirrhosis, liver failure, or liver cancer without monitoring and treatment.', 'Fatigue|Jaundice|Dark urine|Abdominal pain|Nausea|Often no symptoms', 'Hepatitis B virus spread through blood, sexual contact, and parent-to-child transmission.', 'Vaccination|Safe sex|Avoid sharing needles or razors|Screen pregnancy and exposed contacts', 'Some acute infections need monitoring only; chronic infection may require antiviral therapy and liver surveillance.', 'Seek urgent care for confusion, severe jaundice, vomiting blood, severe abdominal swelling, or bleeding.', ['HBsAg', 'Anti-HBs and anti-HBc', 'HBV DNA', 'Liver enzymes', 'Ultrasound surveillance when chronic'], ['Liver', 'Vaccine-preventable', 'Chronic infection']),
            $this->catalogItem('hepatitis-c', 'Hepatitis C', 'Infectious Diseases', 'Viral liver infection', 'Moderate', 'Hepatitis C is a bloodborne viral infection that is often silent for years but can usually be cured with direct-acting antivirals.', 'Often no symptoms|Fatigue|Jaundice|Abdominal discomfort|Dark urine|Easy bruising when advanced', 'Hepatitis C virus spread mainly through blood exposure, including unsafe injections or unsterile equipment.', 'Use sterile injection equipment|Screen blood products|Test people with risk factors|Avoid sharing personal items with blood exposure', 'Direct-acting antivirals can cure most infections. Liver fibrosis assessment and follow-up are important.', 'Urgent care is needed for signs of liver failure such as confusion, severe jaundice, vomiting blood, or severe swelling.', ['HCV antibody', 'HCV RNA', 'Genotype when needed', 'Liver fibrosis assessment', 'Liver enzymes'], ['Liver', 'Curable infection', 'Bloodborne']),
            $this->catalogItem('antimicrobial-resistant-gonorrhea', 'Drug-Resistant Gonorrhea', 'Antimicrobial Resistance', 'Bacterial sexually transmitted infection', 'High', 'Gonorrhea is a sexually transmitted bacterial infection. Drug-resistant strains make accurate treatment and follow-up increasingly important.', 'Painful urination|Discharge|Pelvic pain|Testicular pain|Rectal symptoms|Often asymptomatic', 'Neisseria gonorrhoeae spread through sexual contact; resistance emerges from antibiotic pressure and incomplete control.', 'Condoms|Regular STI screening|Partner notification and treatment|Avoid sex until treatment is complete', 'Treatment depends on current local guidance and susceptibility patterns. Partners need evaluation.', 'Urgent care is needed for severe pelvic pain, fever, pregnancy with symptoms, swollen painful testicle, or eye infection.', ['NAAT', 'Culture for susceptibility', 'Testing at exposed sites', 'Screening for chlamydia, HIV, syphilis'], ['STI', 'AMR', 'Public health']),
            $this->catalogItem('carbapenem-resistant-enterobacterales', 'Carbapenem-Resistant Enterobacterales (CRE)', 'Antimicrobial Resistance', 'Priority bacterial pathogen', 'Critical', 'CRE are hard-to-treat bacteria that can cause severe infections in hospitalized or medically complex patients.', 'Fever|Sepsis signs|Urinary symptoms|Wound infection|Pneumonia symptoms depending on site', 'Enterobacterales with resistance mechanisms that defeat carbapenem antibiotics, often linked to healthcare exposure.', 'Infection-control precautions|Hand hygiene|Antimicrobial stewardship|Device-care bundles|Screening in high-risk facilities', 'Treatment requires specialist antibiotic selection guided by cultures and susceptibility testing.', 'Seek urgent care for fever with low blood pressure, confusion, fast breathing, or suspected sepsis.', ['Blood/urine/wound culture', 'Antimicrobial susceptibility testing', 'Resistance mechanism testing', 'Sepsis labs'], ['WHO BPPL', 'Healthcare-associated', 'AMR']),
            $this->catalogItem('mrsa-infection', 'MRSA Infection', 'Antimicrobial Resistance', 'Drug-resistant bacterial infection', 'High', 'MRSA is Staphylococcus aureus resistant to common beta-lactam antibiotics. It can cause skin infections, pneumonia, bloodstream infection, or sepsis.', 'Painful red skin swelling|Pus|Fever|Wound warmth|Cough or chest pain if pneumonia', 'Methicillin-resistant Staphylococcus aureus spread by contact, wounds, shared items, or healthcare exposure.', 'Hand hygiene|Cover wounds|Avoid sharing towels or razors|Clean equipment|Follow infection-control guidance', 'Treatment depends on infection severity and antibiotic susceptibility; drainage may be needed for abscesses.', 'Urgent care for rapidly spreading redness, fever, severe pain, confusion, low blood pressure, or breathing difficulty.', ['Wound culture', 'Blood cultures if severe', 'Susceptibility testing', 'Imaging if deep infection suspected'], ['AMR', 'Skin infection', 'Sepsis risk']),
            $this->catalogItem('sepsis', 'Sepsis', 'Critical Care', 'Life-threatening infection response', 'Critical', 'Sepsis is a life-threatening organ dysfunction caused by the body response to infection. Early recognition and treatment save lives.', 'Fever or low temperature|Confusion|Fast breathing|Low blood pressure|Severe weakness|Low urine', 'Any infection can trigger sepsis, including pneumonia, urinary infection, abdominal infection, skin infection, or bloodstream infection.', 'Prevent infections|Vaccination|Wound care|Early treatment of worsening infections|Careful catheter and device hygiene', 'Emergency treatment may include antibiotics, fluids, oxygen, source control, and intensive monitoring.', 'Call emergency services for suspected sepsis, especially confusion, fainting, mottled skin, fast breathing, or very low urine.', ['Vital signs', 'Lactate', 'Blood cultures', 'CBC and chemistry', 'Source imaging when needed'], ['Emergency', 'Critical care', 'Infection']),
            $this->catalogItem('hypertension', 'Hypertension', 'Cardiovascular', 'Chronic cardiovascular condition', 'Moderate', 'Hypertension means blood pressure remains elevated over time, raising risk for stroke, heart attack, kidney disease, and vision loss.', 'Often none|Headache when severe|Chest discomfort|Shortness of breath|Dizziness|Blurred vision', 'Genetics, age, salt intake, kidney disease, obesity, alcohol, stress, sleep apnea, and some medicines can contribute.', 'Check blood pressure regularly|Reduce salt|Stay active|Stop smoking|Treat sleep apnea|Take medicines consistently', 'Lifestyle measures and medications such as ACE inhibitors, ARBs, calcium channel blockers, diuretics, or beta blockers may be used.', 'Urgent care for chest pain, severe headache, weakness on one side, confusion, fainting, or severe shortness of breath.', ['Blood pressure log', 'Kidney function', 'Urine albumin', 'ECG', 'Lipid and diabetes screening'], ['Chronic', 'Stroke risk', 'Heart health']),
            $this->catalogItem('coronary-artery-disease', 'Coronary Artery Disease', 'Cardiovascular', 'Chronic cardiovascular condition', 'High', 'Coronary artery disease occurs when narrowed heart arteries reduce blood flow and increase the risk of angina and heart attack.', 'Chest pressure|Shortness of breath|Fatigue|Pain radiating to arm, jaw, or back|Nausea with exertion', 'Atherosclerosis from cholesterol plaque, smoking, diabetes, hypertension, family history, age, and inflammatory risk factors.', 'Do not smoke|Control blood pressure and diabetes|Use statins when prescribed|Exercise safely|Follow heart-healthy eating', 'Treatment may include antiplatelet therapy, statins, blood pressure control, anti-anginal medicines, procedures, and cardiac rehabilitation.', 'Emergency care for chest pain lasting more than a few minutes, sweating, fainting, severe breathlessness, or new weakness.', ['ECG', 'Troponin if acute', 'Stress testing', 'Coronary CT angiography', 'Cardiac catheterization'], ['Heart attack risk', 'Chronic', 'Emergency symptoms']),
            $this->catalogItem('heart-failure', 'Heart Failure', 'Cardiovascular', 'Chronic cardiovascular condition', 'High', 'Heart failure means the heart cannot pump or fill well enough for the body needs. It can often be managed with medicines and monitoring.', 'Shortness of breath|Leg swelling|Rapid weight gain|Fatigue|Trouble lying flat|Night waking with breathlessness', 'Coronary disease, hypertension, valve disease, cardiomyopathy, arrhythmias, diabetes, and some toxins can lead to heart failure.', 'Control blood pressure|Limit sodium if advised|Track weight|Take medicines|Avoid smoking|Follow up after hospital visits', 'Treatment may include guideline-directed medicines, diuretics, devices, procedures, and management of underlying causes.', 'Urgent care for severe breathlessness, pink frothy sputum, fainting, chest pain, confusion, or rapid swelling.', ['BNP or NT-proBNP', 'Echocardiogram', 'ECG', 'Chest X-ray', 'Kidney and electrolyte tests'], ['Chronic', 'Cardiology', 'Fluid monitoring']),
            $this->catalogItem('stroke-warning-signs', 'Stroke Warning Signs', 'Neurology', 'Acute neurologic emergency', 'Critical', 'A stroke occurs when blood flow to part of the brain is blocked or bleeding occurs. Fast action can preserve brain function.', 'Face drooping|Arm weakness|Speech difficulty|Sudden confusion|Vision loss|Dizziness or trouble walking', 'Blood clots, narrowed arteries, bleeding in the brain, atrial fibrillation, hypertension, diabetes, smoking, and high cholesterol.', 'Control blood pressure|Treat diabetes and cholesterol|Do not smoke|Manage atrial fibrillation|Take prescribed medicines', 'Treatment is time-sensitive and may include clot-busting medicine, thrombectomy, bleeding control, rehabilitation, and risk reduction.', 'Call emergency services immediately for any sudden stroke warning sign. Do not wait to see if symptoms pass.', ['FAST screen', 'Brain CT or MRI', 'CT angiography', 'Glucose', 'ECG', 'Coagulation tests'], ['Emergency', 'Brain', 'Time-sensitive']),
            $this->catalogItem('type-2-diabetes', 'Type 2 Diabetes', 'Endocrine & Metabolic', 'Chronic metabolic condition', 'Moderate', 'Type 2 diabetes is a chronic condition where insulin resistance and reduced insulin response keep blood glucose elevated.', 'Increased thirst|Frequent urination|Fatigue|Blurred vision|Slow wound healing|Tingling feet', 'Insulin resistance, family history, excess weight, low activity, age, gestational diabetes history, and some medicines.', 'Balanced meals|Physical activity|Weight management when appropriate|Screening if at risk|Medication adherence', 'Treatment may include nutrition planning, metformin, GLP-1 or SGLT2 medicines, insulin, and risk-factor control.', 'Urgent care for confusion, severe dehydration, vomiting, very high glucose, or low glucose that does not improve quickly.', ['A1C', 'Fasting glucose', 'Random glucose', 'Kidney and urine albumin tests', 'Eye and foot exams'], ['Chronic', 'Metabolic', 'Kidney risk']),
            $this->catalogItem('chronic-kidney-disease', 'Chronic Kidney Disease', 'Renal & Urology', 'Chronic kidney condition', 'Moderate', 'Chronic kidney disease is gradual loss of kidney function. Early detection can slow progression and reduce cardiovascular risk.', 'Often none early|Leg swelling|Fatigue|Foamy urine|High blood pressure|Nausea when advanced', 'Diabetes, hypertension, glomerular disease, inherited conditions, recurrent injury, and some medicines or toxins.', 'Control diabetes and blood pressure|Avoid unnecessary NSAIDs|Check kidney labs if at risk|Use kidney-protective medicines when prescribed', 'Treatment focuses on slowing progression, managing complications, and planning dialysis or transplant if advanced.', 'Urgent care for severe swelling, shortness of breath, confusion, chest pain, very low urine, or high potassium symptoms.', ['eGFR/creatinine', 'Urine albumin-creatinine ratio', 'Electrolytes', 'Renal ultrasound', 'Blood pressure monitoring'], ['Chronic', 'Kidney', 'Cardiovascular risk']),
            $this->catalogItem('copd', 'Chronic Obstructive Pulmonary Disease (COPD)', 'Respiratory', 'Chronic respiratory condition', 'Moderate', 'COPD is a long-term lung disease that limits airflow, commonly linked with tobacco smoke and other inhaled exposures.', 'Chronic cough|Mucus|Shortness of breath|Wheezing|Frequent chest infections|Exercise limitation', 'Smoking, biomass smoke, occupational dusts, air pollution, asthma overlap, and alpha-1 antitrypsin deficiency.', 'Stop smoking|Vaccination|Avoid smoke and pollutants|Pulmonary rehabilitation|Use inhalers correctly', 'Treatment may include bronchodilator inhalers, inhaled steroids for selected patients, rehab, oxygen, and exacerbation plans.', 'Urgent care for severe breathlessness, blue lips, confusion, chest pain, or symptoms not improving with rescue treatment.', ['Spirometry', 'Pulse oximetry', 'Chest imaging', 'Alpha-1 testing when indicated', 'Blood gas if severe'], ['Chronic', 'Lung health', 'Smoking-related']),
            $this->catalogItem('asthma', 'Asthma', 'Respiratory', 'Chronic airway condition', 'Moderate', 'Asthma is a chronic airway condition with inflammation and variable narrowing that can cause wheeze, cough, and breathing attacks.', 'Wheezing|Cough|Chest tightness|Shortness of breath|Night symptoms|Exercise symptoms', 'Airway inflammation triggered by allergens, infections, smoke, pollution, cold air, exercise, or occupational exposures.', 'Avoid triggers|Use controller medicines as prescribed|Check inhaler technique|Keep an action plan|Vaccination where appropriate', 'Treatment may include rescue inhalers, inhaled corticosteroids, combination inhalers, biologics, and trigger management.', 'Emergency care for blue lips, severe breathlessness, confusion, inability to speak, or rescue medicine not helping.', ['Spirometry', 'Peak flow', 'Bronchodilator response', 'Allergy testing', 'FeNO when available'], ['Chronic', 'Airway', 'Action plan']),
            $this->catalogItem('lung-cancer', 'Lung Cancer', 'Oncology', 'Cancer', 'High', 'Lung cancer begins in lung tissue and is a leading cause of cancer death. Earlier diagnosis can improve treatment options.', 'Persistent cough|Coughing blood|Chest pain|Unexplained weight loss|Shortness of breath|Hoarseness', 'Tobacco smoke, radon, air pollution, occupational exposures, family history, and prior lung disease can contribute.', 'Avoid tobacco|Test homes for radon where relevant|Reduce occupational exposure|Screen eligible high-risk adults with low-dose CT', 'Treatment may include surgery, radiation, chemotherapy, targeted therapy, immunotherapy, and palliative support.', 'Urgent care for coughing large amounts of blood, severe breathlessness, chest pain, confusion, or spinal cord compression symptoms.', ['Low-dose CT for screening', 'Chest CT', 'Biopsy', 'Molecular testing', 'PET scan', 'Pulmonary function tests'], ['Cancer', 'Screening', 'Respiratory']),
            $this->catalogItem('breast-cancer', 'Breast Cancer', 'Oncology', 'Cancer', 'Moderate', 'Breast cancer is a common cancer that may be found through screening or evaluation of a breast change.', 'Breast lump|Skin dimpling|Nipple discharge|Nipple inversion|Breast pain|Swollen nodes', 'Risk factors include age, genetics, hormone exposure, breast density, alcohol, obesity after menopause, and family history.', 'Screening mammography when recommended|Know personal risk|Limit alcohol|Stay active|Genetic counseling for high-risk families', 'Treatment depends on stage and biology and may include surgery, radiation, endocrine therapy, chemotherapy, targeted therapy, or immunotherapy.', 'Urgent evaluation for rapidly worsening swelling, infection-like breast changes, neurologic symptoms, or breathing difficulty in advanced disease.', ['Mammography', 'Ultrasound', 'Core biopsy', 'ER/PR/HER2 testing', 'Staging imaging when needed'], ['Cancer', 'Screening', 'Women health']),
            $this->catalogItem('cervical-cancer-hpv', 'Cervical Cancer and HPV', 'Oncology', 'HPV-related cancer prevention', 'Moderate', 'Persistent high-risk HPV infection can lead to cervical cancer over time. Vaccination and screening prevent many cases.', 'Often none early|Abnormal bleeding|Pelvic pain|Bleeding after sex|Unusual discharge', 'High-risk human papillomavirus infection is the central cause; smoking and immune suppression increase risk.', 'HPV vaccination|Cervical screening|Follow up abnormal results|Condoms reduce but do not eliminate HPV risk', 'Precancer treatment prevents cancer; cancer treatment may include surgery, radiation, chemotherapy, or systemic therapy.', 'Urgent care for heavy bleeding, severe pelvic pain, fainting, or signs of advanced disease.', ['HPV test', 'Pap smear', 'Colposcopy', 'Biopsy', 'Staging imaging when cancer diagnosed'], ['Cancer prevention', 'Vaccine-preventable', 'Screening']),
            $this->catalogItem('depression', 'Depression', 'Mental Health', 'Mental health condition', 'Moderate', 'Depression is a common mental health condition affecting mood, energy, sleep, thinking, and daily function.', 'Persistent low mood|Loss of interest|Sleep changes|Appetite changes|Low energy|Guilt|Poor concentration', 'Biological vulnerability, life stress, trauma, medical illness, medications, substance use, and social factors can contribute.', 'Build support|Treat chronic illness|Reduce alcohol or drug misuse|Sleep routine|Early help when symptoms persist', 'Treatment may include psychotherapy, antidepressants, exercise support, social interventions, and safety planning.', 'Emergency help is needed for suicidal thoughts, self-harm risk, psychosis, inability to care for self, or severe agitation.', ['Clinical assessment', 'PHQ-9', 'Suicide risk assessment', 'Medical review for contributing conditions'], ['Mental health', 'Safety planning', 'Chronic care']),
            $this->catalogItem('anxiety-disorders', 'Anxiety Disorders', 'Mental Health', 'Mental health condition', 'Moderate', 'Anxiety disorders cause excessive fear or worry that interferes with daily life and may produce physical symptoms.', 'Excessive worry|Panic attacks|Restlessness|Fast heartbeat|Avoidance|Sleep problems|Muscle tension', 'Genetics, stress, trauma, medical conditions, stimulants, and learned fear patterns can contribute.', 'Reduce stimulants|Sleep routine|Stress skills|Early therapy|Treat medical contributors', 'Treatment may include cognitive behavioral therapy, exposure-based therapy, medicines, and lifestyle support.', 'Urgent help if anxiety includes chest pain not previously assessed, fainting, suicidal thoughts, psychosis, or inability to function.', ['Clinical assessment', 'GAD-7', 'Panic assessment', 'Medical evaluation when symptoms mimic physical illness'], ['Mental health', 'Panic', 'Behavioral health']),
            $this->catalogItem('alzheimer-disease', 'Alzheimer Disease', 'Neurology', 'Neurodegenerative condition', 'Moderate', 'Alzheimer disease is a progressive brain disorder that affects memory, thinking, behavior, and independence.', 'Memory loss|Getting lost|Repeating questions|Word-finding trouble|Behavior changes|Difficulty managing tasks', 'Age, genetics, vascular risk factors, brain changes with amyloid and tau, and other health factors contribute.', 'Protect hearing and vision|Manage blood pressure and diabetes|Stay socially and mentally active|Exercise safely|Plan early support', 'Treatment may include cognitive medicines, newer disease-specific therapies for selected patients, caregiver support, and safety planning.', 'Urgent care for sudden confusion, falls, dehydration, fever, stroke symptoms, or unsafe behavior.', ['Cognitive testing', 'Functional assessment', 'Blood tests for reversible causes', 'Brain imaging', 'Specialist biomarker testing when appropriate'], ['Neurodegenerative', 'Memory', 'Caregiver support']),
            $this->catalogItem('parkinson-disease', 'Parkinson Disease', 'Neurology', 'Neurodegenerative movement disorder', 'Moderate', 'Parkinson disease is a progressive movement disorder involving dopamine pathways, movement symptoms, and non-motor features.', 'Tremor|Slowness|Stiffness|Balance problems|Small handwriting|Constipation|Sleep behavior changes', 'Combination of age, genetics, environmental factors, and loss of dopamine-producing neurons.', 'Exercise|Fall prevention|Medication review|Sleep and constipation care|Neurology follow-up', 'Treatment may include levodopa, dopamine agonists, therapy, exercise, and device-based options for selected patients.', 'Urgent care for sudden severe confusion, inability to move after missed medicines, falls with injury, hallucinations with danger, or aspiration.', ['Neurologic exam', 'Medication response', 'Imaging when diagnosis unclear', 'Assessment of gait and swallowing'], ['Movement disorder', 'Chronic', 'Neurology']),
            $this->catalogItem('migraine', 'Migraine', 'Neurology', 'Neurologic headache disorder', 'Moderate', 'Migraine is a neurologic disorder causing recurrent headache attacks and sensory sensitivity, sometimes with aura.', 'Throbbing headache|Nausea|Light sensitivity|Sound sensitivity|Aura|Worsening with movement', 'Genetics, brain signaling changes, sleep disruption, dehydration, hormones, stress, and certain triggers can contribute.', 'Regular sleep and meals|Hydration|Trigger diary|Limit medication overuse|Preventive treatment when frequent', 'Treatment may include acute pain medicines, triptans, gepants, anti-nausea medicines, preventives, and lifestyle planning.', 'Emergency care for worst headache of life, new weakness, fever with stiff neck, head injury, sudden vision loss, or confusion.', ['Clinical history', 'Headache diary', 'Neurologic exam', 'Brain imaging when red flags exist'], ['Headache', 'Neurology', 'Trigger tracking']),
            $this->catalogItem('epilepsy', 'Epilepsy', 'Neurology', 'Seizure disorder', 'Moderate', 'Epilepsy is a tendency toward recurrent unprovoked seizures caused by abnormal electrical activity in the brain.', 'Seizures|Staring spells|Jerking movements|Loss of awareness|Confusion after episodes|Tongue biting', 'Genetics, brain injury, stroke, infection, developmental conditions, tumors, or unknown causes.', 'Medication adherence|Avoid sleep deprivation|Safety planning|Avoid seizure triggers when known|Driving guidance by local law', 'Treatment may include anti-seizure medicines, surgery evaluation, devices, diet therapy, and rescue plans.', 'Emergency care for seizure lasting more than five minutes, repeated seizures, injury, pregnancy, first seizure, or breathing problems.', ['EEG', 'Brain MRI', 'Metabolic tests', 'Seizure history', 'Medication levels when needed'], ['Seizure', 'Safety plan', 'Neurology']),
            $this->catalogItem('osteoarthritis', 'Osteoarthritis', 'Musculoskeletal', 'Degenerative joint condition', 'Low', 'Osteoarthritis is a common joint condition involving cartilage wear, pain, stiffness, and reduced function.', 'Joint pain|Stiffness after rest|Reduced range of motion|Swelling|Grinding sensation|Activity limitation', 'Age, prior injury, repetitive load, genetics, joint alignment, and excess weight can contribute.', 'Strengthening exercises|Weight management if relevant|Joint protection|Avoid prolonged inactivity|Injury prevention', 'Treatment may include exercise therapy, pain medicines, topical treatments, injections, assistive devices, or joint replacement.', 'Urgent care for hot swollen joint with fever, sudden severe pain, inability to bear weight after injury, or neurologic symptoms.', ['Clinical exam', 'X-ray when needed', 'Function assessment', 'Labs if inflammatory arthritis suspected'], ['Joint health', 'Chronic pain', 'Mobility']),
            $this->catalogItem('rheumatoid-arthritis', 'Rheumatoid Arthritis', 'Musculoskeletal', 'Autoimmune inflammatory condition', 'Moderate', 'Rheumatoid arthritis is an autoimmune disease that causes inflammatory joint pain and can affect organs if uncontrolled.', 'Symmetric joint swelling|Morning stiffness|Fatigue|Warm tender joints|Hand or wrist pain', 'Immune system inflammation involving genetics, smoking, hormones, and environmental triggers.', 'Do not smoke|Early rheumatology care|Protect joints|Vaccination review before immune medicines|Monitor cardiovascular risk', 'Disease-modifying medicines can prevent joint damage; treatment may include methotrexate, biologics, steroids, and therapy.', 'Urgent care for fever on immune medicines, chest pain, severe shortness of breath, sudden neurologic symptoms, or hot single joint.', ['RF and anti-CCP', 'ESR/CRP', 'Joint exam', 'X-ray or ultrasound', 'CBC and liver/kidney monitoring'], ['Autoimmune', 'Inflammation', 'Rheumatology']),
            $this->catalogItem('systemic-lupus-erythematosus', 'Systemic Lupus Erythematosus', 'Autoimmune & Inflammatory', 'Autoimmune systemic condition', 'Moderate', 'Systemic lupus erythematosus is an autoimmune disease that can affect skin, joints, kidneys, blood, brain, heart, and lungs.', 'Fatigue|Joint pain|Photosensitive rash|Mouth ulcers|Hair loss|Chest pain with breathing|Swelling', 'Immune dysregulation influenced by genetics, hormones, UV light, infections, and some medicines.', 'Sun protection|Medication adherence|Regular kidney and blood monitoring|Vaccination planning|Avoid smoking', 'Treatment may include hydroxychloroquine, steroids, immunosuppressants, biologics, and organ-specific care.', 'Urgent care for chest pain, severe headache, seizure, confusion, kidney warning signs, severe infection, or shortness of breath.', ['ANA', 'Anti-dsDNA and complement', 'Urinalysis', 'CBC', 'Kidney tests', 'Organ-specific imaging'], ['Autoimmune', 'Kidney risk', 'Systemic']),
            $this->catalogItem('celiac-disease', 'Celiac Disease', 'Gastroenterology', 'Autoimmune digestive condition', 'Low', 'Celiac disease is an immune reaction to gluten that damages the small intestine and can affect nutrition and growth.', 'Diarrhea|Bloating|Weight loss|Anemia|Fatigue|Mouth ulcers|Poor growth in children', 'Genetic susceptibility with immune reaction to gluten proteins in wheat, barley, and rye.', 'Do not start gluten-free diet before testing if possible|Family screening when indicated|Nutritional monitoring|Strict gluten avoidance after diagnosis', 'Treatment is a lifelong gluten-free diet with nutritional correction and follow-up for healing.', 'Urgent care for severe dehydration, major weight loss, blood in stool, or severe abdominal pain.', ['tTG-IgA and total IgA', 'Endoscopy with biopsy', 'Nutritional labs', 'Bone health assessment when needed'], ['Autoimmune', 'Digestive', 'Nutrition']),
            $this->catalogItem('inflammatory-bowel-disease', 'Inflammatory Bowel Disease', 'Gastroenterology', 'Chronic inflammatory bowel condition', 'Moderate', 'Inflammatory bowel disease includes Crohn disease and ulcerative colitis, causing chronic inflammation of the digestive tract.', 'Diarrhea|Blood in stool|Abdominal pain|Weight loss|Fatigue|Urgency|Fever during flares', 'Immune-mediated inflammation influenced by genetics, gut microbiome, smoking, and environmental factors.', 'Do not smoke|Medication adherence|Vaccination review|Nutrition support|Regular colon cancer surveillance when indicated', 'Treatment may include aminosalicylates, steroids, immunomodulators, biologics, small molecules, nutrition, or surgery.', 'Urgent care for severe bleeding, dehydration, fever with severe pain, toxic megacolon symptoms, or obstruction signs.', ['Colonoscopy with biopsy', 'CRP and fecal calprotectin', 'Stool infection tests', 'Imaging for Crohn disease'], ['Chronic', 'Digestive', 'Immune therapy']),
            $this->catalogItem('nonalcoholic-fatty-liver-disease', 'Fatty Liver Disease', 'Gastroenterology', 'Metabolic liver condition', 'Low', 'Fatty liver disease involves excess fat in the liver and can progress to inflammation, fibrosis, cirrhosis, and liver cancer in some patients.', 'Often none|Fatigue|Right upper abdominal discomfort|Elevated liver enzymes|Signs of cirrhosis when advanced', 'Insulin resistance, obesity, type 2 diabetes, dyslipidemia, genetics, and alcohol or medication contributors depending on subtype.', 'Weight management where appropriate|Physical activity|Diabetes and lipid control|Avoid excess alcohol|Review medicines', 'Treatment focuses on metabolic risk control, weight loss when indicated, and specialist monitoring for fibrosis.', 'Urgent care for confusion, vomiting blood, severe jaundice, black stools, or abdominal swelling.', ['Liver enzymes', 'Ultrasound', 'Fibrosis score', 'Elastography', 'Metabolic labs'], ['Liver', 'Metabolic', 'Chronic']),
            $this->catalogItem('polycystic-ovary-syndrome', 'Polycystic Ovary Syndrome (PCOS)', 'Maternal & Reproductive', 'Endocrine reproductive condition', 'Low', 'PCOS is a hormonal condition that can affect menstrual cycles, androgen levels, fertility, metabolism, and long-term diabetes risk.', 'Irregular periods|Acne|Excess facial hair|Scalp hair thinning|Weight changes|Infertility concerns', 'Insulin resistance, genetic factors, ovarian hormone changes, and metabolic risk patterns contribute.', 'Regular metabolic screening|Physical activity|Nutrition support|Cycle tracking|Discuss fertility plans early', 'Treatment may include lifestyle support, hormonal contraception, metformin, anti-androgen therapy, or ovulation induction when pregnancy desired.', 'Urgent care for very heavy bleeding, severe pelvic pain, fainting, or pregnancy with severe pain.', ['Clinical criteria', 'Androgen tests', 'Pelvic ultrasound when needed', 'A1C/glucose', 'Lipid testing'], ['Women health', 'Metabolic', 'Fertility']),
            $this->catalogItem('preeclampsia', 'Preeclampsia', 'Maternal & Reproductive', 'Pregnancy hypertensive emergency risk', 'High', 'Preeclampsia is high blood pressure with organ involvement during pregnancy or after delivery. It can endanger parent and baby.', 'High blood pressure|Severe headache|Vision changes|Right upper belly pain|Swelling|Shortness of breath', 'Placental and vascular dysfunction with higher risk in first pregnancy, prior preeclampsia, kidney disease, hypertension, diabetes, or multiples.', 'Prenatal care|Blood pressure monitoring|Aspirin prevention for eligible patients|Report warning symptoms promptly', 'Management depends on pregnancy stage and severity and may include monitoring, medicines, magnesium, and delivery planning.', 'Emergency care for severe headache, vision changes, chest pain, breathlessness, seizures, severe belly pain, or very high blood pressure.', ['Blood pressure checks', 'Urine protein', 'Platelets', 'Liver and kidney tests', 'Fetal monitoring'], ['Pregnancy', 'Emergency symptoms', 'Blood pressure']),
            $this->catalogItem('sickle-cell-disease', 'Sickle Cell Disease', 'Hematology', 'Inherited blood disorder', 'Moderate', 'Sickle cell disease is an inherited hemoglobin disorder causing painful crises, anemia, infections, and organ complications.', 'Pain crises|Fatigue|Jaundice|Swollen hands or feet|Frequent infections|Shortness of breath', 'Inherited hemoglobin S variants cause red blood cells to sickle, block flow, and break down early.', 'Vaccination|Penicillin in young children when prescribed|Hydration|Avoid extreme temperatures|Regular specialist follow-up', 'Treatment may include hydroxyurea, transfusion programs, pain plans, infection prevention, and curative transplant or gene therapy for selected patients.', 'Urgent care for fever, chest pain, breathing difficulty, stroke symptoms, severe pain, priapism, or sudden weakness.', ['Hemoglobin electrophoresis', 'CBC and reticulocytes', 'Transcranial Doppler in children', 'Organ monitoring'], ['Inherited', 'Pain crisis', 'Hematology']),
            $this->catalogItem('anemia-iron-deficiency', 'Iron Deficiency Anemia', 'Hematology', 'Nutritional blood condition', 'Low', 'Iron deficiency anemia happens when iron stores are too low to make enough healthy red blood cells.', 'Fatigue|Pale skin|Shortness of breath on exertion|Dizziness|Fast heartbeat|Craving ice or nonfood items', 'Blood loss, heavy menstruation, pregnancy, low iron intake, poor absorption, or gastrointestinal bleeding.', 'Iron-rich diet|Treat heavy bleeding|Screen high-risk groups|Avoid unnecessary NSAIDs if bleeding risk|Follow iron therapy correctly', 'Treatment includes oral or IV iron and evaluation of the cause, especially in adults with possible blood loss.', 'Urgent care for chest pain, fainting, severe breathlessness, black stools, vomiting blood, or very heavy bleeding.', ['CBC', 'Ferritin', 'Iron studies', 'Reticulocyte count', 'GI evaluation when indicated'], ['Nutrition', 'Blood', 'Fatigue']),
            $this->catalogItem('obesity', 'Obesity', 'Endocrine & Metabolic', 'Chronic metabolic condition', 'Moderate', 'Obesity is a chronic, relapsing medical condition associated with higher risk of diabetes, heart disease, sleep apnea, liver disease, and some cancers.', 'Increased body fat|Joint pain|Sleep apnea symptoms|Fatigue|Metabolic complications|Reduced mobility in some patients', 'Genetics, environment, medicines, sleep, stress, endocrine factors, diet patterns, and activity patterns interact.', 'Supportive nutrition care|Physical activity|Sleep optimization|Reduce weight stigma|Treat related conditions early', 'Treatment may include intensive lifestyle support, anti-obesity medicines, bariatric procedures, and management of complications.', 'Urgent care for chest pain, severe breathlessness, stroke symptoms, or rapidly worsening swelling.', ['BMI and waist assessment', 'A1C/glucose', 'Lipids', 'Liver tests', 'Sleep apnea screening'], ['Metabolic', 'Chronic', 'Prevention']),
            $this->catalogItem('thyroid-disease', 'Thyroid Disease', 'Endocrine & Metabolic', 'Endocrine condition', 'Low', 'Thyroid disease includes underactive or overactive thyroid states that affect metabolism, heart rhythm, energy, weight, and mood.', 'Fatigue|Weight change|Heat or cold intolerance|Palpitations|Constipation or diarrhea|Hair changes|Neck swelling', 'Autoimmune thyroid disease, iodine imbalance, nodules, medicines, pregnancy-related changes, or pituitary disorders.', 'Screen when symptomatic or high risk|Medication adherence|Avoid unsupervised iodine supplements|Pregnancy monitoring when needed', 'Treatment may include thyroid hormone replacement, anti-thyroid medicines, radioactive iodine, surgery, or nodule surveillance.', 'Urgent care for severe palpitations, chest pain, confusion, very high fever, severe weakness, or airway symptoms from neck swelling.', ['TSH', 'Free T4', 'T3 when hyperthyroid suspected', 'Thyroid antibodies', 'Ultrasound for nodules'], ['Hormone', 'Metabolic', 'Autoimmune']),
            $this->catalogItem('eczema', 'Eczema', 'Dermatology', 'Inflammatory skin condition', 'Low', 'Eczema is an inflammatory skin condition with dry, itchy, irritated patches and impaired skin-barrier function.', 'Dry itchy skin|Red or darker patches|Scaling|Cracking|Oozing during flares|Sleep disruption from itch', 'Skin barrier weakness, genetics, allergies, irritants, weather changes, stress, and infections can trigger flares.', 'Fragrance-free moisturizers|Gentle cleansers|Avoid known irritants|Keep nails short|Use prescribed anti-inflammatory treatment early', 'Treatment may include moisturizers, topical steroids, calcineurin inhibitors, infection care, phototherapy, or advanced medicines.', 'Seek care for fever, pus, spreading pain, rapidly worsening redness, or eye-area involvement.', ['Clinical exam', 'Allergy assessment when indicated', 'Skin culture if infection suspected', 'Patch testing for contact allergy'], ['Skin', 'Itch', 'Barrier care']),
            $this->catalogItem('psoriasis', 'Psoriasis', 'Dermatology', 'Immune-mediated skin condition', 'Low', 'Psoriasis is an immune-mediated condition causing scaly plaques and sometimes joint inflammation.', 'Thick scaly plaques|Itching|Nail pitting|Scalp scale|Joint pain in psoriatic arthritis', 'Immune dysregulation influenced by genetics, infections, stress, injury to skin, smoking, and some medicines.', 'Avoid smoking|Treat infections|Moisturize|Monitor joint symptoms|Manage cardiovascular risk factors', 'Treatment may include topical therapies, phototherapy, systemic medicines, or biologics depending on severity.', 'Urgent care for widespread painful redness, fever, dehydration, eye pain, or severe joint swelling.', ['Clinical exam', 'Skin biopsy when unclear', 'Joint assessment', 'Metabolic risk screening'], ['Skin', 'Immune-mediated', 'Joint risk']),
            $this->catalogItem('meningitis', 'Meningitis', 'Infectious Diseases', 'Central nervous system infection', 'Critical', 'Meningitis is inflammation of the membranes around the brain and spinal cord. Bacterial meningitis can become fatal quickly.', 'Fever|Severe headache|Stiff neck|Confusion|Vomiting|Light sensitivity|Rash in some infections', 'Bacteria, viruses, fungi, or parasites can infect the meninges; risk varies by age, immunity, vaccines, and exposures.', 'Vaccination|Prompt care for suspected infection|Prophylaxis for certain close contacts|Avoid sharing respiratory secretions during outbreaks', 'Treatment depends on cause; suspected bacterial meningitis requires urgent antibiotics and hospital care.', 'Emergency care is required for fever with stiff neck, confusion, seizure, rash, severe headache, or infant lethargy.', ['Lumbar puncture CSF tests', 'Blood cultures', 'PCR panels', 'Brain imaging when needed before LP'], ['Emergency', 'Neurology', 'Vaccine-preventable causes']),
            $this->catalogItem('lyme-disease', 'Lyme Disease', 'Tropical & Vector-Borne', 'Tick-borne bacterial disease', 'Moderate', 'Lyme disease is a tick-borne infection that can affect skin, joints, heart, and nervous system if untreated.', 'Expanding rash|Fever|Fatigue|Headache|Joint pain|Facial palsy|Palpitations in some cases', 'Borrelia bacteria transmitted by infected Ixodes ticks after attachment.', 'Tick checks|Repellent|Protective clothing|Prompt tick removal|Landscape measures in endemic areas', 'Antibiotics are effective, with regimen depending on stage and organ involvement.', 'Urgent care for facial weakness, severe headache with stiff neck, fainting, chest pain, or heart rhythm symptoms.', ['Clinical rash recognition', 'Two-tier antibody testing', 'ECG if carditis suspected', 'Joint fluid testing rarely'], ['Vector-borne', 'Tick', 'Neurologic risk']),
            $this->catalogItem('zika-virus-disease', 'Zika Virus Disease', 'Tropical & Vector-Borne', 'Viral vector-borne disease', 'Moderate', 'Zika virus often causes mild illness but infection during pregnancy can cause congenital complications.', 'Mild fever|Rash|Joint pain|Red eyes|Headache|Often no symptoms', 'Zika virus spread by Aedes mosquitoes and sexual transmission; pregnancy exposure is a major concern.', 'Mosquito bite prevention|Pregnancy travel counseling|Condoms after exposure|Community mosquito control', 'Care is supportive. Pregnancy exposures need specialist follow-up and fetal monitoring.', 'Urgent care for neurologic weakness, severe dehydration, or pregnancy exposure needing prompt counseling.', ['PCR early after symptoms', 'Serology with cross-reactivity caution', 'Pregnancy ultrasound follow-up'], ['Vector-borne', 'Pregnancy', 'Travel medicine']),
            $this->catalogItem('yellow-fever', 'Yellow Fever', 'Tropical & Vector-Borne', 'Viral hemorrhagic vector-borne disease', 'High', 'Yellow fever is a mosquito-borne viral disease that can cause fever, jaundice, bleeding, and organ failure.', 'Fever|Headache|Muscle pain|Jaundice|Vomiting|Bleeding in severe cases', 'Yellow fever virus transmitted by infected mosquitoes in endemic regions.', 'Yellow fever vaccination for eligible travelers and residents|Mosquito avoidance|Outbreak control', 'No specific antiviral treatment; care is supportive with monitoring for liver, kidney, and bleeding complications.', 'Urgent care for jaundice, bleeding, confusion, severe vomiting, low urine, or shock signs.', ['PCR early', 'Serology', 'Liver and kidney tests', 'Coagulation tests', 'Travel history'], ['Vector-borne', 'Vaccine-preventable', 'Hemorrhagic fever']),
            $this->catalogItem('plague', 'Plague', 'High-Consequence Pathogens', 'Bacterial zoonotic disease', 'Critical', 'Plague is a serious infection caused by Yersinia pestis. Pneumonic plague can spread person-to-person and requires urgent treatment.', 'Fever|Painful swollen lymph nodes|Weakness|Cough or bloody sputum in pneumonic plague|Sepsis signs', 'Yersinia pestis transmitted by fleas, infected animals, or respiratory droplets in pneumonic disease.', 'Avoid contact with sick or dead animals|Flea control|Protective equipment for suspected cases|Public health response', 'Early antibiotics are essential and close contacts may need prophylaxis.', 'Seek emergency care for fever after rodent/flea exposure, painful swollen nodes, coughing blood, or sepsis signs.', ['Culture or PCR', 'Blood cultures', 'Bubo aspirate testing', 'Chest imaging if pneumonic'], ['Zoonotic', 'Bacterial', 'Emergency']),
            $this->catalogItem('leptospirosis', 'Leptospirosis', 'Infectious Diseases', 'Bacterial zoonotic disease', 'Moderate', 'Leptospirosis is a bacterial infection linked to water or soil contaminated by animal urine. Severe disease can affect kidneys, liver, lungs, or brain.', 'Fever|Severe muscle pain|Headache|Red eyes|Jaundice|Low urine|Coughing blood in severe cases', 'Leptospira bacteria enter through skin breaks or mucous membranes after contaminated water, flood, or animal exposure.', 'Avoid contaminated floodwater|Protective footwear and gloves|Rodent control|Safe occupational practices', 'Antibiotics are used; severe cases need hospital care for kidney, liver, or lung complications.', 'Urgent care for jaundice, low urine, severe breathlessness, confusion, bleeding, or dehydration.', ['PCR early', 'Serology', 'Kidney and liver tests', 'CBC', 'Exposure history'], ['Zoonotic', 'Water exposure', 'Flood risk']),
            $this->catalogItem('typhoid-fever', 'Typhoid Fever', 'Infectious Diseases', 'Bacterial food and waterborne disease', 'Moderate', 'Typhoid fever is a systemic infection caused by Salmonella Typhi, often linked to unsafe food or water.', 'Sustained fever|Headache|Abdominal pain|Constipation or diarrhea|Weakness|Rash spots', 'Salmonella Typhi spread through contaminated food or water from human carriers.', 'Safe water and food hygiene|Vaccination for travelers or high-risk settings|Handwashing|Sanitation', 'Antibiotics are selected based on resistance patterns; hydration and monitoring are important.', 'Urgent care for confusion, severe abdominal pain, bleeding, persistent vomiting, dehydration, or shock.', ['Blood culture', 'Stool culture', 'CBC', 'Liver tests', 'Travel and exposure history'], ['Waterborne', 'Travel medicine', 'AMR watch']),
            $this->catalogItem('meningococcal-disease', 'Meningococcal Disease', 'Infectious Diseases', 'Bacterial invasive disease', 'Critical', 'Meningococcal disease can cause meningitis or bloodstream infection and can progress rapidly within hours.', 'Fever|Severe headache|Stiff neck|Purple rash|Leg pain|Cold hands|Confusion', 'Neisseria meningitidis spread through respiratory secretions during close contact.', 'Vaccination where recommended|Chemoprophylaxis for close contacts|Prompt isolation and treatment', 'Emergency antibiotics and hospital care are essential when suspected.', 'Call emergency services for fever with purple rash, confusion, stiff neck, severe headache, or rapidly worsening illness.', ['Blood culture', 'CSF analysis', 'PCR', 'Coagulation and sepsis labs'], ['Emergency', 'Vaccine-preventable', 'Sepsis']),
        ];

        foreach ($rows as $index => $row) {
            $rows[$index]['id'] = -($index + 1);
            $rows[$index]['status'] = 'published';
            $rows[$index]['featured_image_path'] = null;
            $rows[$index]['seo_title'] = $row['disease_name'] . ' Patient Guide';
            $rows[$index]['seo_description'] = substr(strip_tags((string) $row['overview']), 0, 155);
            $rows[$index]['created_at'] = '2026-06-01 00:00:00';
            $rows[$index]['updated_at'] = '2026-06-01 00:00:00';
        }

        $catalog = $rows;
        return $catalog;
    }

    private function catalogItem(
        string $slug,
        string $name,
        string $category,
        string $conditionType,
        string $riskLevel,
        string $overview,
        string $symptoms,
        string $causes,
        string $prevention,
        string $treatment,
        string $emergency,
        array $diagnostics,
        array $tags
    ): array {
        return [
            'slug' => $slug,
            'disease_name' => $name,
            'overview' => '<p>' . $overview . '</p>',
            'symptoms' => $this->listHtml($symptoms),
            'causes' => $this->listHtml($causes),
            'prevention' => $this->listHtml($prevention),
            'treatment' => '<p>' . $treatment . '</p>',
            'emergency_notes' => '<p>' . $emergency . '</p>',
            'taxonomy_category' => $category,
            'condition_type' => $conditionType,
            'risk_level' => $riskLevel,
            'diagnostic_tests' => $diagnostics,
            'risk_tags' => $tags,
        ];
    }

    private function listHtml(string $items): string
    {
        $html = '<ul>';
        foreach (array_filter(array_map('trim', explode('|', $items))) as $item) {
            $html .= '<li>' . htmlspecialchars($item, ENT_QUOTES, 'UTF-8') . '</li>';
        }
        return $html . '</ul>';
    }
}
