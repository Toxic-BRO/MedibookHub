<?php
/**
 * includes/ai_health.php — AI Health Assistant engine.
 *
 * Provides:
 *   - ai_analyze_symptoms($text):  returns specialty, urgency, do's, don'ts, matched doctors
 *   - ai_find_best_doctors($specialty):  returns verified doctors from DB for a given specialty
 *   - ai_check_doctor_lateness():  checks for pending appointments that have been waiting too long
 *                                  and sends reminder notifications to the doctor
 *
 * No external API required — uses a built-in medical knowledge base.
 */

declare(strict_types=1);


/* --------------------------------------------------------------------- */
/*  Symptom-to-specialty knowledge base                                  */
/* --------------------------------------------------------------------- */

/**
 * Each entry maps a set of symptom keywords to:
 *   - specialty:   which doctor specialty to recommend
 *   - urgency:     'normal' | 'soon' | 'urgent'
 *   - dos:         things the patient SHOULD do
 *   - donts:       things the patient should NOT do
 *   - description: a short explanation of the condition
 *   - self_care:   optional self-care advice before seeing the doctor
 */
function ai_knowledge_base(): array
{
    return [

        /* ---- Cardiology ---- */
        [
            'keywords' => ['chest pain', 'chest tightness', 'heart palpitation', 'shortness of breath',
                           'high blood pressure', 'hypertension', 'chest discomfort', 'chest pressure',
                           'irregular heartbeat', 'heart pain', 'chest ache', 'cardiac', 'heart attack',
                           'chest burning', 'left arm pain', 'arm numbness'],
            'specialty' => 'Cardiology',
            'urgency' => 'urgent',
            'description' => 'Your symptoms may be related to your heart or blood vessels. Chest pain, palpitations, or breathlessness can signal a cardiac issue that needs prompt evaluation.',
            'dos' => [
                'Seek medical attention promptly — chest symptoms should never be ignored.',
                'Note when the pain or discomfort started, how long it lasts, and what triggers it.',
                'Keep a record of your blood pressure readings if you have a monitor at home.',
                'Bring a list of all current medications and supplements.',
                'Rest and avoid physical exertion until you are evaluated.',
            ],
            'donts' => [
                'Do not ignore chest pain or assume it is just indigestion.',
                'Do not perform strenuous exercise or lift heavy objects.',
                'Do not smoke or consume alcohol before your appointment.',
                'Do not stop any prescribed heart medication without consulting a doctor.',
            ],
            'self_care' => 'If chest pain is severe, radiating to the arm or jaw, or accompanied by sweating and nausea, go to the emergency room immediately rather than booking an online appointment.',
        ],

        /* ---- Dermatology ---- */
        [
            'keywords' => ['skin rash', 'rash', 'acne', 'eczema', 'skin infection', 'itching',
                           'skin allergy', 'hives', 'skin irritation', 'dry skin', 'skin bump',
                           'mole', 'skin cancer', 'psoriasis', 'skin peeling', 'blister',
                           'skin discoloration', 'dark spot', 'pimple', 'whitehead', 'blackhead',
                           'dandruff', 'fungal infection skin', 'ringworm'],
            'specialty' => 'Dermatology',
            'urgency' => 'normal',
            'description' => 'Skin conditions like rashes, acne, or unusual growths are best evaluated by a dermatologist. They can diagnose the cause and recommend topical or oral treatment.',
            'dos' => [
                'Take a clear photo of the affected area in good lighting to show the doctor.',
                'Note when the problem started and if anything makes it better or worse.',
                'Keep the area clean and avoid applying unknown creams or home remedies.',
                'Mention any new cosmetics, detergents, or foods that may have triggered it.',
            ],
            'donts' => [
                'Do not scratch or pick at the affected skin — it can cause infection or scarring.',
                'Do not apply steroid creams without a prescription.',
                'Do not use harsh soaps or hot water on irritated skin.',
                'Do not try to pop pimples or cysts yourself.',
            ],
            'self_care' => 'A cool compress can relieve itching temporarily. Use a mild, fragrance-free moisturizer on dry or irritated skin until your appointment.',
        ],

        /* ---- Pediatrics ---- */
        [
            'keywords' => ['child fever', 'baby sick', 'child cough', 'pediatric', 'kid fever',
                           'infant', 'toddler', 'child vaccination', 'baby not eating',
                           'child stomach pain', 'kid not eating', 'child diarrhea',
                           'baby crying', 'child ear infection', 'tonsils child', 'child health',
                           'newborn', 'baby rash', 'child vomiting', 'growth problem child',
                           'my son', 'my daughter', 'my child', 'my kid', 'my baby'],
            'specialty' => 'Pediatrics',
            'urgency' => 'soon',
            'description' => 'Symptoms involving children — fever, cough, feeding issues, or unusual behavior — should be assessed by a pediatrician who specializes in child health.',
            'dos' => [
                'Record the child\'s temperature, when the fever started, and any medications given.',
                'Keep the child hydrated with fluids — water, breast milk, or oral rehydration solution.',
                'Note any changes in behavior, appetite, or sleep patterns.',
                'Bring the child\'s vaccination record to the appointment.',
            ],
            'donts' => [
                'Do not give adult medications to a child — dosage can be dangerous.',
                'Do not use cold water or ice baths to reduce fever — use lukewarm sponging instead.',
                'Do not force the child to eat if they have no appetite, but keep them hydrated.',
                'Do not ignore a fever above 39C (102F) in a child under 3 — seek urgent care.',
            ],
            'self_care' => 'Keep the child comfortable and hydrated. For fever, lukewarm sponging and age-appropriate paracetamol can help. Seek emergency care if the child is unusually drowsy, has difficulty breathing, or has a seizure.',
        ],

        /* ---- Neurology ---- */
        [
            'keywords' => ['headache', 'migraine', 'dizziness', 'fainting', 'seizure',
                           'numbness', 'tingling', 'memory loss', 'confusion', 'stroke',
                           'paralysis', 'weakness in limbs', 'speech difficulty',
                           'balance problem', 'tremor', 'shaking', 'head injury',
                           'concussion', 'chronic headache', 'cluster headache',
                           'face numbness', 'vision loss sudden'],
            'specialty' => 'Neurology',
            'urgency' => 'soon',
            'description' => 'Symptoms affecting the brain or nervous system — headaches, dizziness, numbness, or seizures — need neurological evaluation to identify the cause.',
            'dos' => [
                'Keep a symptom diary: note when headaches occur, their duration, and triggers.',
                'Record any factors that worsen or relieve the symptoms (light, sound, food, rest).',
                'Note if there is a family history of neurological conditions.',
                'Bring a list of all medications you currently take.',
            ],
            'donts' => [
                'Do not drive if you are experiencing dizziness or fainting spells.',
                'Do not ignore sudden severe headaches — they may require emergency care.',
                'Do not self-medicate with painkillers excessively — it can cause rebound headaches.',
                'Do not skip meals or become dehydrated, as this can trigger headaches.',
            ],
            'self_care' => 'For migraines, rest in a dark, quiet room. Stay hydrated. Seek emergency care if you experience sudden severe headache, weakness on one side, or difficulty speaking.',
        ],

        /* ---- Orthopedics ---- */
        [
            'keywords' => ['back pain', 'joint pain', 'knee pain', 'arthritis', 'bone fracture',
                           'sprain', 'muscle pain', 'neck pain', 'shoulder pain', 'hip pain',
                           'leg pain', 'broken bone', 'dislocation', 'sports injury',
                           'joint swelling', 'stiffness', 'bone pain', 'spine pain',
                           'lower back pain', 'slipped disc', 'sciatica', 'tendon',
                           'ligament', 'muscle tear'],
            'specialty' => 'Orthopedics',
            'urgency' => 'normal',
            'description' => 'Bone, joint, and muscle pain — including injuries and chronic conditions like arthritis — are treated by an orthopedic specialist.',
            'dos' => [
                'Rest the affected area and avoid putting weight or stress on it.',
                'Apply ice to reduce swelling for 15-20 minutes at a time.',
                'Note how the pain started (sudden injury vs gradual onset) and what makes it worse.',
                'Elevate the injured limb if possible to reduce swelling.',
            ],
            'donts' => [
                'Do not continue the activity that caused the pain.',
                'Do not apply heat to a fresh injury — use ice for the first 48 hours.',
                'Do not force a stiff joint to move beyond its comfortable range.',
                'Do not take strong painkillers and then continue using the injured area.',
            ],
            'self_care' => 'Follow the RICE protocol for acute injuries: Rest, Ice, Compression, Elevation. Over-the-counter anti-inflammatory medication can provide temporary relief.',
        ],

        /* ---- ENT (Ear, Nose, Throat) ---- */
        [
            'keywords' => ['ear pain', 'ear infection', 'hearing loss', 'ringing in ear',
                           'tinnitus', 'sore throat', 'strep throat', 'tonsillitis',
                           'nose block', 'sinus', 'sinusitis', 'nosebleed',
                           'runny nose', 'allergic rhinitis', 'nasal congestion',
                           'ear wax', 'ear discharge', 'throat pain', 'difficulty swallowing',
                           'hoarseness', 'voice loss', 'snoring', 'sleep apnea'],
            'specialty' => 'ENT',
            'urgency' => 'normal',
            'description' => 'Ear, nose, and throat conditions — infections, hearing issues, sinus problems, or throat pain — are treated by an ENT specialist.',
            'dos' => [
                'Note when symptoms started and whether they affect one or both sides.',
                'For sore throat, gargle with warm salt water and stay hydrated.',
                'For sinus pressure, steam inhalation can provide temporary relief.',
                'Avoid loud noises if you are experiencing ear symptoms.',
            ],
            'donts' => [
                'Do not put anything inside your ear canal — no cotton buds or sharp objects.',
                'Do not blow your nose too hard, especially if you have a nosebleed.',
                'Do not consume very hot or cold foods if you have throat pain.',
                'Do not ignore persistent hoarseness lasting more than 2 weeks.',
            ],
            'self_care' => 'Warm salt water gargles and steam inhalation can relieve throat and sinus discomfort. Over-the-counter pain relievers may help with ear pain temporarily.',
        ],

        /* ---- Ophthalmology ---- */
        [
            'keywords' => ['eye pain', 'eye irritation', 'blurred vision', 'red eye',
                           'eye infection', 'conjunctivitis', 'pink eye', 'dry eyes',
                           'eye swelling', 'vision problem', 'eye floaters',
                           'cataract', 'glaucoma', 'eye discharge', 'light sensitivity',
                           'eye allergy', 'double vision', 'vision loss',
                           'eye itching', 'stye', 'eye burning'],
            'specialty' => 'Ophthalmology',
            'urgency' => 'soon',
            'description' => 'Eye problems — pain, vision changes, infections, or irritation — should be evaluated by an eye specialist to prevent complications.',
            'dos' => [
                'Note when symptoms started and whether they affect one or both eyes.',
                'Remove contact lenses if you wear them and switch to glasses.',
                'Keep the eye area clean and avoid touching or rubbing it.',
                'Note any recent eye injuries or chemical exposures.',
            ],
            'donts' => [
                'Do not rub or press on the affected eye.',
                'Do not use someone else\'s eye drops or leftover prescription drops.',
                'Do not wear contact lenses until the problem is resolved.',
                'Do not ignore sudden vision loss — seek emergency care.',
            ],
            'self_care' => 'For dry eyes, preservative-free artificial tears can provide relief. A clean, warm compress can soothe irritation. Seek emergency care for sudden vision loss or eye injury.',
        ],

        /* ---- Gastroenterology ---- */
        [
            'keywords' => ['stomach pain', 'abdominal pain', 'acidity', 'gastric',
                           'ulcer', 'indigestion', 'bloating', 'constipation',
                           'diarrhea', 'nausea', 'vomiting', 'heartburn',
                           'acid reflux', 'GERD', 'gallbladder', 'liver',
                           'ibs', 'irritable bowel', 'stomach cramp',
                           'gas', 'loss of appetite', 'blood in stool',
                           'stomach ache', 'belly pain', 'food poisoning'],
            'specialty' => 'Gastroenterology',
            'urgency' => 'normal',
            'description' => 'Digestive issues — stomach pain, acidity, bowel problems, or liver concerns — are treated by a gastroenterologist.',
            'dos' => [
                'Keep a food diary to identify trigger foods.',
                'Stay hydrated, especially if you have diarrhea or vomiting.',
                'Eat small, frequent meals rather than large ones.',
                'Note if the pain is related to meals, and where exactly it occurs.',
            ],
            'donts' => [
                'Do not eat spicy, oily, or heavy foods if you have stomach pain.',
                'Do not take painkillers like ibuprofen on an empty stomach — they can worsen ulcers.',
                'Do not ignore blood in your stool or persistent vomiting.',
                'Do not self-medicate with antibiotics for digestive issues.',
            ],
            'self_care' => 'Drink plenty of fluids. Eat bland foods like rice, toast, and bananas if you have an upset stomach. Avoid alcohol and caffeine. Seek emergency care for severe abdominal pain or blood in vomit.',
        ],

        /* ---- Psychiatry ---- */
        [
            'keywords' => ['depression', 'anxiety', 'panic attack', 'stress',
                           'insomnia', 'sleep problem', 'mental health', 'mood swing',
                           'sadness', 'fear', 'phobia', 'OCD', 'obsessive',
                           'trauma', 'PTSD', 'bipolar', 'self harm',
                           'suicidal', 'emotional', 'can not sleep',
                           'feeling hopeless', 'no motivation', 'social anxiety',
                           'mental stress', 'work stress', 'exam stress'],
            'specialty' => 'Psychiatry',
            'urgency' => 'soon',
            'description' => 'Mental health concerns — persistent sadness, anxiety, sleep problems, or stress — are best addressed by a psychiatrist or mental health professional.',
            'dos' => [
                'Talk to someone you trust about how you are feeling.',
                'Maintain a regular sleep schedule and daily routine.',
                'Note your symptoms: when they occur, how long, and what may trigger them.',
                'Practice deep breathing or meditation for anxiety relief.',
            ],
            'donts' => [
                'Do not isolate yourself — staying connected helps.',
                'Do not use alcohol or drugs to cope with emotional distress.',
                'Do not stop any prescribed psychiatric medication abruptly.',
                'Do not dismiss your feelings — seeking help is a sign of strength, not weakness.',
            ],
            'self_care' => 'If you are having thoughts of self-harm, please contact a crisis helpline or go to the nearest emergency room immediately. You are not alone, and help is available.',
        ],

        /* ---- Dentistry ---- */
        [
            'keywords' => ['tooth pain', 'toothache', 'cavity', 'gum bleeding',
                           'gum swelling', 'tooth decay', 'dental', 'wisdom tooth',
                           'tooth sensitivity', 'broken tooth', 'mouth ulcer',
                           'bad breath', 'gingivitis', 'root canal',
                           'teeth whitening', 'braces', 'orthodontic',
                           'tooth crack', 'jaw pain', 'dental implant'],
            'specialty' => 'Dentistry',
            'urgency' => 'normal',
            'description' => 'Dental problems — toothache, cavities, gum issues, or oral pain — should be examined by a dentist to prevent complications.',
            'dos' => [
                'Rinse your mouth with warm salt water to reduce pain and inflammation.',
                'Note when the pain started and what triggers it (hot, cold, chewing).',
                'Keep the area clean by gentle brushing and flossing.',
                'Save any broken tooth fragment if applicable — bring it to the dentist.',
            ],
            'donts' => [
                'Do not apply aspirin directly to the gums — it can cause chemical burns.',
                'Do not chew on the side with the painful tooth.',
                'Do not consume extremely hot, cold, or sugary foods.',
                'Do not ignore persistent gum bleeding — it may indicate gum disease.',
            ],
            'self_care' => 'Over-the-counter pain relievers can help temporarily. A cold compress on the cheek can reduce swelling. Avoid very hot or cold foods until you see the dentist.',
        ],

        /* ---- General Medicine (fallback) ---- */
        [
            'keywords' => ['fever', 'flu', 'cough', 'cold', 'sore throat', 'fatigue',
                           'tiredness', 'body ache', 'weakness', 'general checkup',
                           'check up', 'annual checkup', 'blood test', 'diabetes',
                           'thyroid', 'blood sugar', 'cholesterol', 'weight loss',
                           'weight gain', 'viral fever', 'allergy', 'runny nose',
                           'sneezing', 'head cold', 'body pain', 'malaise',
                           'feeling unwell', 'sick'],
            'specialty' => 'General Medicine',
            'urgency' => 'normal',
            'description' => 'General symptoms like fever, cough, fatigue, or routine health concerns can be evaluated by a general physician. They can also manage chronic conditions like diabetes or thyroid issues.',
            'dos' => [
                'Rest and stay hydrated with plenty of fluids.',
                'Monitor your temperature if you have a fever.',
                'Note all your symptoms, their duration, and severity.',
                'Bring any previous test results or medical records to your appointment.',
            ],
            'donts' => [
                'Do not self-medicate with antibiotics without a prescription.',
                'Do not ignore persistent symptoms lasting more than a few days.',
                'Do not skip meals if you feel weak — eat light, nutritious food.',
                'Do not take multiple over-the-counter medications without checking interactions.',
            ],
            'self_care' => 'Rest, fluids, and a balanced diet help with most common illnesses. Over-the-counter fever reducers can manage temperature. Seek care if symptoms persist beyond 3-5 days or worsen.',
        ],

        /* ---- Endocrinology ---- */
        [
            'keywords' => ['diabetes', 'high blood sugar', 'low blood sugar', 'thyroid',
                           'hyperthyroid', 'hypothyroid', 'hormone', 'insulin',
                           'blood glucose', 'frequent urination', 'excessive thirst',
                           'weight change', 'sweating excessive', 'heat intolerance',
                           'cold intolerance', 'goiter', 'thyroid nodule'],
            'specialty' => 'Endocrinology',
            'urgency' => 'normal',
            'description' => 'Hormone and metabolic conditions — diabetes, thyroid disorders, or other endocrine issues — require specialized evaluation.',
            'dos' => [
                'Monitor your blood sugar regularly if you have a glucose meter.',
                'Keep a record of your readings, diet, and symptoms.',
                'Note any family history of diabetes or thyroid disorders.',
                'Bring all current medications and recent lab results.',
            ],
            'donts' => [
                'Do not skip meals if you are diabetic — it can cause low blood sugar.',
                'Do not stop thyroid medication without consulting your doctor.',
                'Do not consume excessive sugary foods or drinks.',
                'Do not ignore symptoms of very high or very low blood sugar.',
            ],
            'self_care' => 'Maintain a balanced diet with regular meal times. Stay active. For diabetics, always carry a quick sugar source in case of low blood sugar episodes.',
        ],

        /* ---- Pulmonology ---- */
        [
            'keywords' => ['breathing problem', 'asthma', 'pneumonia', 'chronic cough',
                           'wheezing', 'breathlessness', 'lung', 'phlegm',
                           'coughing blood', 'shortness breath chronic', 'COPD',
                           'bronchitis', 'chest congestion', 'respiratory',
                           'cough with mucus', 'long cough', 'night cough'],
            'specialty' => 'Pulmonology',
            'urgency' => 'soon',
            'description' => 'Respiratory symptoms — persistent cough, wheezing, or breathing difficulty — need evaluation by a lung specialist.',
            'dos' => [
                'Note when breathing problems occur (at rest, during activity, at night).',
                'Avoid smoke, dust, and known allergens.',
                'Use a prescribed inhaler if you have one, and bring it to the appointment.',
                'Keep track of how long the cough or breathing issue has lasted.',
            ],
            'donts' => [
                'Do not smoke or expose yourself to secondhand smoke.',
                'Do not ignore worsening breathlessness — seek urgent care.',
                'Do not use someone else\'s inhaler or medication.',
                'Do not lie flat if you have breathing difficulty at night — prop yourself up.',
            ],
            'self_care' => 'If you have asthma, use your rescue inhaler as prescribed. Avoid known triggers. Seek emergency care for severe breathing difficulty or blue-tinged lips.',
        ],

        /* ---- Urology ---- */
        [
            'keywords' => ['urinary tract infection', 'UTI', 'kidney stone',
                           'frequent urination', 'painful urination', 'blood in urine',
                           'urine problem', 'bladder', 'prostate', 'kidney pain',
                           'lower abdomen pain male', 'urinary incontinence',
                           'erectile dysfunction', 'male problem', 'testicle pain'],
            'specialty' => 'Urology',
            'urgency' => 'normal',
            'description' => 'Urinary and kidney-related symptoms — pain, infections, or changes in urination — should be evaluated by a urologist.',
            'dos' => [
                'Drink plenty of water to help flush the urinary system.',
                'Note the color and frequency of your urine.',
                'Avoid holding urine for long periods.',
                'Note any pain and its location (lower back, lower abdomen, during urination).',
            ],
            'donts' => [
                'Do not ignore blood in your urine — seek prompt evaluation.',
                'Do not consume excessive caffeine or alcohol, which can irritate the bladder.',
                'Do not take painkillers that may worsen kidney function without advice.',
                'Do not self-treat urinary infections with leftover antibiotics.',
            ],
            'self_care' => 'Drink plenty of water. Avoid bladder irritants like caffeine and spicy foods. Seek urgent care for severe pain, high fever, or inability to urinate.',
        ],

        /* ---- Gynecology ---- */
        [
            'keywords' => ['pregnancy', 'menstrual', 'period problem', 'irregular period',
                           'pregnant', 'gynecology', 'women health', 'ovarian',
                           'fertility', 'miscarriage', 'menopause', 'vaginal',
                           'pelvic pain female', 'pregnancy checkup', 'antenatal',
                           'PCOS', 'fibroid', 'heavy bleeding', 'missed period',
                           'pregnancy symptoms'],
            'specialty' => 'Gynecology',
            'urgency' => 'normal',
            'description' => 'Women\'s health concerns — pregnancy, menstrual issues, fertility, or pelvic pain — are best managed by a gynecologist.',
            'dos' => [
                'Track your menstrual cycle dates, flow, and any associated symptoms.',
                'Note any medications or supplements you are taking.',
                'If pregnant, note the last menstrual period for due date calculation.',
                'Bring previous ultrasound or test results if available.',
            ],
            'donts' => [
                'Do not take any medication during pregnancy without consulting a doctor.',
                'Do not ignore severe abdominal pain or heavy bleeding — seek urgent care.',
                'Do not miss routine prenatal checkups.',
                'Do not self-medicate for menstrual problems.',
            ],
            'self_care' => 'Maintain a healthy diet and exercise routine. Track your cycle regularly. Seek emergency care for severe pelvic pain or very heavy bleeding.',
        ],
    ];
}


/* --------------------------------------------------------------------- */
/*  AI symptom analysis                                                   */
/* --------------------------------------------------------------------- */

/**
 * Analyze patient symptom text and return a structured recommendation.
 *
 * @return array{
 *     matched: bool,
 *     specialty: string,
 *     urgency: string,
 *     description: string,
 *     dos: array<string>,
 *     donts: array<string>,
 *     self_care: string,
 *     doctors: array,
 *     query: string
 * }
 */
function ai_analyze_symptoms(string $text): array
{
    $text = trim($text);
    $lower = ' ' . strtolower($text) . ' ';

    $kb = ai_knowledge_base();

    // Score each entry by counting keyword matches.
    $bestEntry = null;
    $bestScore = 0;
    $bestSpecialty = 'General Medicine';

    // Default fallback entry
    $fallback = [
        'specialty' => 'General Medicine',
        'urgency' => 'normal',
        'description' => 'Your symptoms are general in nature. A general physician can evaluate you and refer you to a specialist if needed.',
        'dos' => [
            'Rest and stay hydrated.',
            'Note all your symptoms and their duration.',
            'Monitor for any changes or new symptoms.',
        ],
        'donts' => [
            'Do not self-medicate without consulting a doctor.',
            'Do not ignore symptoms that persist or worsen.',
        ],
        'self_care' => 'Most general symptoms improve with rest and fluids. Book an appointment if they persist beyond 3-5 days.',
    ];

    foreach ($kb as $entry) {
        $score = 0;
        foreach ($entry['keywords'] as $kw) {
            if (str_contains($lower, ' ' . $kw . ' ') || str_contains($lower, $kw)) {
                $score++;
            }
        }

        if ($score > $bestScore) {
            $bestScore = $score;
            $bestEntry = $entry;
            $bestSpecialty = $entry['specialty'];
        }
    }

    // If no keyword matched, use the fallback.
    if ($bestEntry === null) {
        $bestEntry = $fallback;
    }

    // Find best-matching doctors from the database.
    $doctors = ai_find_best_doctors($bestSpecialty);

    return [
        'matched'        => $bestScore > 0,
        'specialty'      => $bestEntry['specialty'],
        'urgency'        => $bestEntry['urgency'],
        'description'    => $bestEntry['description'],
        'dos'            => $bestEntry['dos'],
        'donts'          => $bestEntry['donts'],
        'self_care'      => $bestEntry['self_care'] ?? '',
        'doctors'        => $doctors,
        'query'          => $text,
    ];
}


/**
 * Find the best-matching verified doctors for a given specialty.
 * Falls back to general medicine if no specialist is found.
 *
 * @return array<int, array>
 */
function ai_find_best_doctors(string $specialty, int $limit = 4): array
{
    // First try to find exact specialty match.
    $sql = "SELECT u.id, u.name, d.specialty, d.fee, d.experience, d.location,
                   d.photo, d.bio,
                   (SELECT ROUND(AVG(r.rating), 1)
                      FROM reviews r
                      JOIN appointments a ON a.id = r.appointment_id
                     WHERE a.doctor_id = u.id) AS avg_rating,
                   (SELECT COUNT(*)
                      FROM reviews r
                      JOIN appointments a ON a.id = r.appointment_id
                     WHERE a.doctor_id = u.id) AS review_count,
                   (SELECT COUNT(*)
                      FROM appointments a
                     WHERE a.doctor_id = u.id AND a.status = 'completed') AS completed_count
              FROM doctors d
              JOIN users u ON u.id = d.user_id
             WHERE d.is_verified = 1
               AND u.status = 'active'
               AND d.specialty = ?
             ORDER BY avg_rating DESC NULLS LAST, completed_count DESC, u.name ASC
             LIMIT " . max(1, $limit);

    $stmt = db()->prepare($sql);
    $stmt->execute([$specialty]);
    $doctors = $stmt->fetchAll();

    // If no exact match found, broaden to all verified doctors.
    if (!$doctors && $specialty !== 'General Medicine') {
        $sql2 = "SELECT u.id, u.name, d.specialty, d.fee, d.experience, d.location,
                        d.photo, d.bio,
                        (SELECT ROUND(AVG(r.rating), 1)
                           FROM reviews r
                           JOIN appointments a ON a.id = r.appointment_id
                          WHERE a.doctor_id = u.id) AS avg_rating,
                        (SELECT COUNT(*)
                           FROM reviews r
                           JOIN appointments a ON a.id = r.appointment_id
                          WHERE a.doctor_id = u.id) AS review_count,
                        (SELECT COUNT(*)
                           FROM appointments a
                          WHERE a.doctor_id = u.id AND a.status = 'completed') AS completed_count
                   FROM doctors d
                   JOIN users u ON u.id = d.user_id
                  WHERE d.is_verified = 1
                    AND u.status = 'active'
                    AND d.specialty = 'General Medicine'
                  ORDER BY avg_rating DESC NULLS LAST, completed_count DESC, u.name ASC
                  LIMIT " . max(1, $limit);

        $doctors = db()->prepare($sql2);
        $doctors->execute();
        $doctors = $doctors->fetchAll();
    }

    // If still nothing, return any verified doctor.
    if (!$doctors) {
        $sql3 = "SELECT u.id, u.name, d.specialty, d.fee, d.experience, d.location,
                        d.photo, d.bio,
                        (SELECT ROUND(AVG(r.rating), 1)
                           FROM reviews r
                           JOIN appointments a ON a.id = r.appointment_id
                          WHERE a.doctor_id = u.id) AS avg_rating,
                        (SELECT COUNT(*)
                           FROM reviews r
                           JOIN appointments a ON a.id = r.appointment_id
                          WHERE a.doctor_id = u.id) AS review_count,
                        (SELECT COUNT(*)
                           FROM appointments a
                          WHERE a.doctor_id = u.id AND a.status = 'completed') AS completed_count
                   FROM doctors d
                   JOIN users u ON u.id = d.user_id
                  WHERE d.is_verified = 1
                    AND u.status = 'active'
                  ORDER BY avg_rating DESC NULLS LAST, completed_count DESC, u.name ASC
                  LIMIT " . max(1, $limit);

        $doctors = db()->query($sql3)->fetchAll();
    }

    return $doctors;
}


/**
 * Urgency badge HTML for the AI suggestion.
 */
function ai_urgency_badge(string $urgency): string
{
    [$cls, $label, $icon] = match ($urgency) {
        'urgent' => ['danger', 'Urgent — seek care soon', 'alert'],
        'soon'   => ['warning text-dark', 'See a doctor soon', 'clock'],
        'normal' => ['success', 'Routine consultation', 'check'],
        default  => ['secondary', 'General consultation', 'check'],
    };

    return '<span class="badge rounded-pill bg-' . $cls . ' ai-urgency-badge">'
        . icon($icon, 14) . ' ' . e($label) . '</span>';
}


/* --------------------------------------------------------------------- */
/*  Doctor lateness reminder system                                       */
/* --------------------------------------------------------------------- */

/**
 * Check for pending appointments that have been waiting beyond a threshold
 * and send reminder notifications to the doctor.
 *
 * Thresholds:
 *   - Pending appointments older than 6 hours → first reminder
 *   - Pending appointments older than 24 hours → urgent reminder
 *
 * @return array{sent: int, details: array<string>}
 */
function ai_check_doctor_lateness(): array
{
    $sent = 0;
    $details = [];

    // Find pending appointments that haven't had a reminder sent yet.
    // We check for a notification with a specific prefix to avoid duplicates.
    $sql = "SELECT a.id, a.patient_id, a.doctor_id, a.`date`, a.`time`,
                   a.created_at, u.name AS patient_name,
                   du.name AS doctor_name,
                   TIMESTAMPDIFF(HOUR, a.created_at, NOW()) AS hours_waiting
              FROM appointments a
              JOIN users u  ON u.id = a.patient_id
              JOIN users du ON du.id = a.doctor_id
             WHERE a.status = 'pending'
               AND a.`date` >= CURDATE()
             ORDER BY a.created_at ASC";

    $stmt = db()->prepare($sql);
    $stmt->execute();
    $pending = $stmt->fetchAll();

    foreach ($pending as $appt) {
        $hoursWaiting = (int) $appt['hours_waiting'];

        // Only send reminders for appointments waiting more than 6 hours.
        if ($hoursWaiting < 6) {
            continue;
        }

        // Check if a reminder notification was already sent for this appointment.
        $checkSql = "SELECT COUNT(*) FROM notifications
                      WHERE user_id = ?
                        AND message LIKE ?
                        AND is_read = 0";
        $checkStmt = db()->prepare($checkSql);

        $isUrgent = $hoursWaiting >= 24;
        $prefix = $isUrgent
            ? 'URGENT: Patient waiting%'
            : 'Reminder: Patient waiting%';

        $checkStmt->execute([(int) $appt['doctor_id'], $prefix]);
        $alreadySent = (int) $checkStmt->fetchColumn() > 0;

        if ($alreadySent) {
            continue;
        }

        // Build the notification message.
        if ($isUrgent) {
            $message = 'URGENT: Patient ' . $appt['patient_name']
                . ' has been waiting ' . $hoursWaiting . ' hours for your response on '
                . fmt_date($appt['date']) . ' at ' . fmt_time($appt['time'])
                . '. Please accept or reject the appointment.';
        } else {
            $message = 'Reminder: Patient ' . $appt['patient_name']
                . ' is still waiting for your response to the appointment on '
                . fmt_date($appt['date']) . ' at ' . fmt_time($appt['time']) . '.';
        }

        notify(
            (int) $appt['doctor_id'],
            $message,
            '/doctor/appointments.php',
            false
        );

        // Also notify the patient that the doctor is being reminded.
        notify(
            (int) $appt['patient_id'],
            'We have sent a reminder to ' . $appt['doctor_name']
            . ' about your pending appointment.',
            '/patient/appointments.php',
            false
        );

        $sent++;
        $details[] = 'Reminded Dr. ' . $appt['doctor_name']
            . ' about pending appointment from ' . $appt['patient_name']
            . ' (waiting ' . $hoursWaiting . 'h)';
    }

    return ['sent' => $sent, 'details' => $details];
}
