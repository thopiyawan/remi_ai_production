<?php

namespace App\Services;

use App\Http\Controllers\RemiDataApiController;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Throwable;

class RemiFrontendDataService
{
    public function dashboard(string $doctorId, int $patientPage = 1, int $taskPage = 1): ?array
    {
        $data = $this->call('dashboard_overview', [
            'doctor_id' => $doctorId,
            'patient_page' => $patientPage,
            'task_page' => $taskPage,
            'patient_per_page' => 1000,
            'task_per_page' => 1000,
        ]);

        if ($data === null) {
            return null;
        }

        $patients = $data['patients'] ?? [];
        $food = $data['ai_food_analysis'] ?? [];
        $foodTotal = (int) ($food['total'] ?? 0);
        $analyzed = (int) ($food['analyzed'] ?? 0);
        $pending = (int) ($food['waiting_review'] ?? 0);
        $continuing = (int) ($patients['continuing_care'] ?? 0);
        $total = (int) ($patients['total'] ?? 0);

        return [
            'updated_label' => 'ข้อมูลล่าสุดวันนี้ • ' . $this->thaiDate(now()),
            'summary' => [
                ['label' => 'ผู้รับบริการทั้งหมด', 'value' => number_format($total), 'detail' => 'เพิ่มวันนี้ ' . number_format((int) ($patients['new_today'] ?? 0)), 'tone' => 'brand', 'icon' => 'fa-user'],
                ['label' => 'AI วิเคราะห์อาหาร', 'value' => number_format($foodTotal), 'detail_lines' => ['ตรวจแล้ว (' . number_format($analyzed) . ')', 'รอตรวจ (' . number_format($pending) . ')'], 'tone' => 'info', 'icon' => 'fa-bolt', 'progress' => $foodTotal > 0 ? round($analyzed / $foodTotal * 100, 1) : 0],
                ['label' => 'รอตรวจสอบ', 'value' => number_format($pending), 'detail' => 'ต้องดำเนินการ', 'tone' => 'warning', 'icon' => 'fa-clock'],
                ['label' => 'ดูแลต่อเนื่อง', 'value' => number_format($continuing), 'detail' => $total > 0 ? round($continuing / $total * 100, 1) . '% ของทั้งหมด' : '0% ของทั้งหมด', 'tone' => 'positive', 'icon' => 'fa-heart-pulse'],
            ],
            'activity' => collect($data['activities_last_7_days'] ?? [])->map(fn ($item) => [
                'day' => Carbon::parse($item['date'])->locale('th')->isoFormat('dd'),
                'value' => (int) ($item['count'] ?? 0),
            ])->values()->all(),
            'weight_signals' => collect(data_get($data, 'complications_and_weight.weight', []))->map(fn ($item) => [
                'label' => $item['label'] ?? 'ไม่ทราบสถานะ',
                'value' => (float) ($item['percentage'] ?? 0),
                'color' => $this->weightColor((int) ($item['code'] ?? 0)),
            ])->values()->all(),
            'weight_total' => (int) data_get($data, 'complications_and_weight.denominator', $total),
            'actions' => collect(data_get($data, 'tasks_today.items', []))->map(function ($item) {
                $isFoodReview = ($item['type'] ?? null) === 'food_review';
                $mealParts = $isFoodReview ? array_values(array_filter([
                    ($item['meal_type'] ?? null) ? 'มื้อ' . $this->mealLabel($item['meal_type']) : null,
                    ($item['meal_date'] ?? null) ? $this->shortDate($item['meal_date']) : null,
                    ($item['meal_time'] ?? null) ? $this->timeOnly($item['meal_time']) . ' น.' : null,
                    !empty($item['reference_id']) ? 'รายการ #' . $item['reference_id'] : null,
                ])) : [];

                return [
                    'type' => $item['type'] ?? null,
                    'reference_id' => $item['reference_id'] ?? null,
                    'patient_id' => $item['patient_user_id'] ?? null,
                    'patient_name' => $item['name'] ?? '-',
                    'hn' => $this->displayHn($item['hn'] ?? null),
                    'title' => $item['type_label'] ?? 'รายการติดตาม',
                    'meta' => ($item['name'] ?? '-')
                        . (($item['hn'] ?? null) ? ' • HN ' . $item['hn'] : '')
                        . ($mealParts ? ' • ' . implode(' • ', $mealParts) : ''),
                    'href' => $isFoodReview && !empty($item['reference_id'])
                        ? route('nutrition-review.index', ['meal_id' => $item['reference_id']])
                        : (!empty($item['patient_user_id']) ? route('patients.show', $item['patient_user_id']) : null),
                    'meal_label' => $isFoodReview && ($item['meal_type'] ?? null) ? 'มื้อ' . $this->mealLabel($item['meal_type']) : null,
                    'meal_date' => $isFoodReview && ($item['meal_date'] ?? null) ? $this->shortDate($item['meal_date']) : null,
                    'meal_time' => $isFoodReview && ($item['meal_time'] ?? null) ? $this->timeOnly($item['meal_time']) . ' น.' : null,
                    'image_url' => $isFoodReview ? ($item['image_url'] ?? null) : null,
                    'food_names' => $item['food_names'] ?? 'ยังไม่มีรายละเอียดรายการอาหาร',
                    'energy' => isset($item['total_calorie']) ? number_format((float) $item['total_calorie']) . ' kcal' : '-',
                    'recommendation' => $item['recommendation'] ?? 'ยังไม่มีคำแนะนำ',
                    'action' => ($item['urgency'] ?? null) === 'high' ? 'เร่งด่วน' : 'ตรวจสอบ',
                    'tone' => ($item['urgency'] ?? null) === 'high' ? 'danger' : 'warning',
                ];
            })->values()->all(),
            'follow_up' => collect(data_get($data, 'patient_list.items', []))->map(fn ($patient) => [
                'id' => $patient['user_id'] ?? (string) ($patient['id'] ?? ''),
                'name' => $patient['name'] ?? '-',
                'hn' => $this->displayHn($patient['hn'] ?? null),
                'pregnancy' => ($patient['gestational_week'] ?? '-') . ' สัปดาห์',
                'status' => $patient['status'] ?? '-',
                'status_tone' => ($patient['status_code'] ?? null) === 0 ? 'warning' : 'success',
                'weight' => $patient['weight_signal'] ?? '-',
                'weight_tone' => $this->weightTone((int) ($patient['weight_status'] ?? 0)),
                'updated' => $this->shortDate($patient['updated_at'] ?? null),
            ])->values()->all(),
            'raw' => $data,
        ];
    }

    public function directory(string $doctorId, int $page = 1): ?array
    {
        $dashboard = $this->dashboard($doctorId, $page, 1);

        if ($dashboard === null) {
            return null;
        }

        $raw = $dashboard['raw'];
        $items = collect(data_get($raw, 'patient_list.items', []))->map(function ($patient) {
            $hasExplicitRisk = collect([
                $patient['compli_diabete'] ?? null,
                $patient['compli_hypertension'] ?? null,
                $patient['compli_preterm_birth'] ?? null,
            ])->contains(fn ($value) => in_array($value, [1, '1', true, 'yes', 'true'], true));

            return [
                'id' => $patient['user_id'] ?? (string) ($patient['id'] ?? ''),
                'hn' => $this->displayHn($patient['hn'] ?? null),
                'name' => $patient['name'] ?? '-',
                'age' => $patient['age'] ?? '-',
                'pregnancy' => $patient['gestational_week'] ?? '-',
                'category' => $hasExplicitRisk ? 'high_risk' : 'all',
                'signal' => $patient['weight_signal'] ?? $patient['status'] ?? '-',
                'weight_label' => $patient['weight_signal'] ?? $this->weightLabel((int) ($patient['weight_status'] ?? 0)),
                'weight_tone' => $this->weightTone((int) ($patient['weight_status'] ?? 0)),
                'weight_status_label' => $patient['weight_signal'] ?? $this->weightLabel((int) ($patient['weight_status'] ?? 0)),
                'weight_status_tone' => $this->weightTone((int) ($patient['weight_status'] ?? 0)),
                'action' => 'ดูรายละเอียดผู้รับบริการ',
            ];
        })->values()->all();

        $total = (int) data_get($raw, 'patient_list.total', count($items));
        $highRisk = collect($items)->where('category', 'high_risk')->count();

        return [
            'filters' => [
                ['key' => 'all', 'label' => 'ทั้งหมด', 'count' => $total],
                ['key' => 'high_risk', 'label' => 'ความเสี่ยงสูง', 'count' => $highRisk],
            ],
            'patients' => $items,
            'total' => $total,
            'per_page' => (int) data_get($raw, 'patient_list.per_page', 50),
            'current_page' => (int) data_get($raw, 'patient_list.current_page', $page),
        ];
    }

    public function patient(string $doctorId, string $userId, string $startDate, string $endDate): ?array
    {
        $detail = $this->call('get_patient_detail', ['doctor_id' => $doctorId, 'user_id' => $userId]);

        if ($detail === null) {
            return null;
        }

        $health = $this->call('patient_health_history', ['doctor_id' => $doctorId, 'user_id' => $userId]) ?? [];
        $nutrition = $this->call('patient_nutrition_history', ['doctor_id' => $doctorId, 'user_id' => $userId, 'start_date' => $startDate, 'end_date' => $endDate, 'meal_per_page' => 1000]) ?? [];
        $diabetes = $this->call('patient_diabetes_history', ['doctor_id' => $doctorId, 'user_id' => $userId, 'start_date' => $startDate, 'end_date' => $endDate]) ?? [];
        $basic = $detail['basic_info'] ?? [];
        $healthBasic = $health['basic_info'] ?? [];
        $weightItems = collect(data_get($detail, 'weight_trend.items', []));
        $healthSummary = $detail['health_record_summary'] ?? [];
        $latestGlucose = collect($diabetes['blood_sugar_history'] ?? [])->first();
        $mealSummary = $nutrition['retrospective_summary'] ?? [];
        $carePlans = collect(data_get($detail, 'care_plans_and_appointments.care_plans.items', []));
        $weightStatusCode = (int) ($basic['weight_status_code'] ?? 0);

        return [
            'id' => $userId,
            'user_id' => $userId,
            'hn' => $this->displayHn($basic['hn'] ?? null),
            'name' => $basic['name'] ?? '-',
            'age' => $basic['age'] ?? '-',
            'pregnancy' => $basic['gestational_week'] ?? '-',
            'category' => ($basic['risk_level'] ?? null) === 'high' ? 'high_risk' : 'mine',
            'due_date' => $this->thaiDate($basic['due_date'] ?? null),
            'due_date_parts' => $this->thaiDateParts($basic['due_date'] ?? null),
            'risk_label' => match ($basic['risk_level'] ?? null) {
                'high' => 'ครรภ์เสี่ยงสูง',
                'low' => 'ความเสี่ยงต่ำ',
                default => 'ไม่มีข้อมูลความเสี่ยง',
            },
            'risk_tone' => match ($basic['risk_level'] ?? null) {
                'high' => 'danger',
                'low' => 'success',
                default => 'muted',
            },
            'is_first_pregnancy' => isset($basic['is_first_pregnancy']) ? (bool) $basic['is_first_pregnancy'] : null,
            'weight' => (float) ($healthBasic['weight'] ?? $basic['weight'] ?? 0),
            'bmi' => $healthBasic['bmi'] ?? null,
            'pre_pregnancy_weight' => (float) ($basic['pre_pregnancy_weight'] ?? 0),
            'weight_status_label' => $this->weightLabel($weightStatusCode),
            'weight_status_tone' => $this->weightTone($weightStatusCode),
            'last_updated' => $this->shortDate($basic['updated_at'] ?? null),
            'summary' => [
                ['label' => 'น้ำหนักล่าสุด', 'value' => number_format((float) ($healthBasic['weight'] ?? $basic['weight'] ?? 0), 1) . ' กก.', 'detail' => 'BMI ' . ($healthBasic['bmi'] ?? '-'), 'icon' => 'fa-solid fa-weight-scale', 'tone' => 'primary'],
                ['label' => 'ออกกำลังกาย', 'value' => number_format((int) data_get($health, 'exercise_history.total', 0)) . ' รายการ', 'detail' => 'ในประวัติที่เลือก', 'icon' => 'fa-solid fa-person-walking', 'tone' => 'info'],
                ['label' => 'บันทึกอาหาร', 'value' => number_format((int) ($mealSummary['total_meals'] ?? 0)) . ' มื้อ', 'detail' => 'วิเคราะห์แล้ว ' . number_format((int) ($mealSummary['analyzed_meals'] ?? 0)) . ' มื้อ', 'icon' => 'fa-solid fa-bowl-food', 'tone' => 'warning'],
                ['label' => 'น้ำตาลล่าสุด', 'value' => $latestGlucose ? number_format((float) $latestGlucose['value']) . ' mg/dL' : 'ไม่มีข้อมูล', 'detail' => $latestGlucose['status_label'] ?? 'ยังไม่มีการบันทึก', 'icon' => 'fa-solid fa-droplet', 'tone' => ($latestGlucose['status'] ?? null) === 'high' ? 'danger' : 'positive'],
            ],
            'weight_chart' => [
                'labels' => $weightItems->map(fn ($item) => 'สัปดาห์ ' . ($item['gestational_week'] ?? '-'))->all(),
                'values' => $weightItems->map(fn ($item) => (float) ($item['weight'] ?? 0))->all(),
            ],
            'health_summary' => $this->healthSummaryTiles($healthSummary, $healthBasic),
            'follow_ups' => $this->followUps($detail),
            'care_plans' => $carePlans->map(fn ($plan) => [
                'date' => $this->shortDate($plan['date'] ?? null),
                'title' => trim(($plan['item'] ?? 'แผนการดูแล') . (isset($plan['dose_units']) ? ' ' . $plan['dose_units'] . ' units' : '')),
                'detail' => $plan['note'] ?? $plan['injection_period'] ?? '',
                'status' => $this->carePlanStatusLabel($plan['status'] ?? null),
                'tone' => ($plan['status'] ?? null) === 'active' ? 'success' : 'muted',
            ])->values()->all(),
            'health' => $this->healthContract($health),
            'nutrition' => $this->nutritionContract($nutrition),
            'glucose_chart' => $this->glucoseChart($diabetes),
            'glucose' => $this->glucoseContract($diabetes),
        ];
    }

    public function nutritionReview(string $doctorId, ?int $mealId = null, int $page = 1, int $perPage = 20): ?array
    {
        $queue = $this->call('food_review_queue', [
            'doctor_id' => $doctorId,
            'page' => max(1, $page),
            'per_page' => min(100, max(1, $perPage)),
        ]);

        if ($queue === null) {
            return null;
        }

        $items = collect($queue['items'] ?? []);
        $selectedMealId = $mealId ?: ($items->first()['id'] ?? null);
        $detail = $selectedMealId
            ? $this->call('food_review_detail', ['doctor_id' => $doctorId, 'meal_id' => $selectedMealId])
            : null;

        if ($selectedMealId && $detail === null) {
            return null;
        }

        $meal = $detail['meal_info'] ?? [];
        $analysis = $detail['ai_analysis'] ?? [];
        $foods = collect($detail['editable_food_items'] ?? []);
        $recommendations = $detail['recommendations_and_notes'] ?? [];
        $image = $detail['food_image'] ?? [];

        return [
            'updated_label' => 'คิวรอตรวจ ' . number_format((int) ($queue['total'] ?? 0)) . ' รายการ',
            'pending_count' => (int) ($queue['total'] ?? 0),
            'selected_meal_id' => $selectedMealId,
            'pagination' => [
                'current_page' => (int) ($queue['current_page'] ?? 1),
                'last_page' => (int) ($queue['last_page'] ?? 1),
                'per_page' => (int) ($queue['per_page'] ?? $perPage),
                'total' => (int) ($queue['total'] ?? 0),
            ],
            'queue' => $items->map(function ($item) {
                $score = (int) round((float) data_get($item, 'ai_confidence.average', 0));
                $meal = $this->mealPresentation($item['meal'] ?? null, $item['time'] ?? null);

                return [
                    'id' => $item['id'],
                    'name' => $item['name'] ?? '-',
                    'score' => $score,
                    'score_tone' => $this->confidenceTone($score),
                    'meal' => $meal['label'],
                    'meal_tone' => $meal['tone'],
                    'time' => substr((string) ($item['time'] ?? ''), 0, 5),
                    'status' => $this->reviewLabel($item['review_status'] ?? 'pending'),
                    'tone' => ($item['urgency'] ?? null) === 'high' ? 'danger' : 'warning',
                ];
            })->values()->all(),
            'master_foods' => [],
            'analysis' => [
                'dish' => $foods->pluck('name')->filter()->join(' + ') ?: 'ไม่มีรายการรอตรวจ',
                'meal' => $this->mealLabel($meal['meal'] ?? null),
                'weight' => $foods->sum(fn ($food) => (float) ($food['weight_g'] ?? 0)) . ' g',
                'confidence' => (int) round((float) data_get($analysis, 'confidence.average', 0)),
                'confidence_tone' => $this->confidenceTone((float) data_get($analysis, 'confidence.average', 0)),
                'warning' => data_get($analysis, 'gdm_analysis.recommendation')
                    ?? (data_get($analysis, 'gdm_analysis.risk_level') ? 'ระดับความเสี่ยง: ' . data_get($analysis, 'gdm_analysis.risk_level') : 'ไม่มีข้อมูลวิเคราะห์'),
                'date' => $image['meal_date'] ?? $meal['date'] ?? null,
                'time' => substr((string) ($image['meal_time'] ?? $meal['time'] ?? ''), 0, 5),
                'image_url' => $image['url'] ?? null,
            ],
            'foods' => $foods->map(function ($food) {
                $confidence = isset($food['confidence_score']) ? (float) $food['confidence_score'] : null;

                return [
                    'id' => $food['id'] ?? null,
                    'name' => $food['food_name'] ?? $food['name'] ?? '-',
                    'amount' => trim(($food['portion'] ?? '-') . ' ' . ($food['unit'] ?? '')),
                    'energy' => number_format((float) ($food['energy_kcal'] ?? 0)) . ' kcal',
                    'portion' => $food['portion'] ?? null,
                    'unit' => $food['unit'] ?? null,
                    'weight_g' => $food['weight_g'] ?? null,
                    'energy_kcal' => $food['energy_kcal'] ?? null,
                    'nutrients' => $food['nutrients'] ?? [],
                    'confidence' => $confidence !== null ? round($confidence) . '%' : '-',
                    'tone' => $confidence !== null ? $this->confidenceTone($confidence) : 'muted',
                ];
            })->values()->all(),
            'totals' => $this->nutritionTotals($analysis),
            'recommendation' => $recommendations['recommendation'] ?? '',
            'note' => $recommendations['latest_note'] ?? '',
        ];
    }

    public function conversations(string $doctorId, array $filters = []): ?array
    {
        $payload = [
            'doctor_id' => $doctorId,
            'page' => max(1, (int) ($filters['page'] ?? 1)),
            'per_page' => min(100, max(1, (int) ($filters['per_page'] ?? 50))),
        ];

        foreach (['keyword', 'start_date', 'end_date', 'sender_id'] as $key) {
            if (isset($filters[$key]) && $filters[$key] !== '') {
                $payload[$key] = $filters[$key];
            }
        }

        $data = $this->call('doctor_patient_conversations', $payload);

        if ($data === null) {
            return null;
        }

        $group = $data['group_info'] ?? [];
        $messageItems = collect(data_get($data, 'messages.items', []));
        $messages = $messageItems->reverse()->map(fn ($message) => [
            'sender' => $message['sender_name'] ?? ($message['sender_type'] === 'patient' ? 'ผู้รับบริการ' : 'REMI'),
            'time' => $this->timeOnly($message['sent_at'] ?? null),
            'body' => $message['message'] ?? '',
            'direction' => $message['sender_type'] === 'doctor' ? 'outgoing' : 'incoming',
            'tone' => $message['sender_type'] === 'doctor' ? 'info' : 'default',
        ])->values()->all();
        $latest = data_get($data, 'conversation_list.0', []);

        $groupConversation = [
            'id' => 'doctor-patient-cohort',
            'source' => 'line',
            'line_conversation_id' => (string) ($group['doctor_id'] ?? $doctorId),
            'type' => 'group',
            'title' => $group['name'] ?? 'กลุ่มผู้รับบริการ',
            'initials' => 'กล',
            'subtitle' => 'กลุ่มดูแลร่วม • สมาชิก ' . number_format((int) ($group['member_count'] ?? 0)) . ' คน',
            'preview' => $latest['latest_message'] ?? 'ยังไม่มีข้อความ',
            'updated' => $this->shortDate($latest['latest_message_at'] ?? null),
            'unread' => (int) ($latest['unread_count'] ?? 0),
            'messages' => $messages,
            'pagination' => [
                'current_page' => (int) data_get($data, 'messages.current_page', 1),
                'last_page' => (int) data_get($data, 'messages.last_page', 1),
                'per_page' => (int) data_get($data, 'messages.per_page', $payload['per_page']),
                'total' => (int) data_get($data, 'messages.total', 0),
            ],
            'search' => $data['search'] ?? [],
        ];

        $directConversations = collect($group['members'] ?? [])->map(function ($member) use ($messageItems) {
            $userId = (string) ($member['user_id'] ?? '');
            $name = (string) ($member['name'] ?? 'ผู้รับบริการ');
            $latestMessage = $messageItems->first(fn ($message) => in_array(
                $userId,
                array_map('strval', $message['patient_user_ids'] ?? []),
                true
            ));

            return [
                'id' => 'patient-' . $userId,
                'patient_user_id' => $userId,
                'source' => 'line',
                'line_conversation_id' => $userId,
                'type' => 'direct',
                'title' => $name,
                'initials' => mb_substr(preg_replace('/\s+/u', '', $name), 0, 2),
                'subtitle' => 'ข้อความ 1:1 • LINE',
                'preview' => $latestMessage['message'] ?? 'เปิดดูประวัติสนทนา',
                'updated' => $this->shortDate($latestMessage['sent_at'] ?? null),
                'unread' => 0,
                'patient' => [
                    'hn' => $this->displayHn($member['hn'] ?? null),
                    'age' => '-',
                    'gestational_age' => '-',
                    'clinical_summary' => 'โหลดรายละเอียดเมื่อเปิดบทสนทนา',
                ],
                'messages' => [],
                'pagination' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 50, 'total' => 0],
                'search' => [],
            ];
        })->all();

        return array_merge([$groupConversation], $directConversations);
    }

    public function patientConversation(string $doctorId, string $userId, array $filters = []): ?array
    {
        $payload = [
            'doctor_id' => $doctorId,
            'user_id' => $userId,
            'page' => max(1, (int) ($filters['page'] ?? 1)),
            'per_page' => min(100, max(1, (int) ($filters['per_page'] ?? 50))),
        ];

        foreach (['keyword', 'start_date', 'end_date', 'sender_id'] as $key) {
            if (isset($filters[$key]) && $filters[$key] !== '') {
                $payload[$key] = $filters[$key];
            }
        }

        $data = $this->call('patient_conversation_detail', $payload);

        if ($data === null) {
            return null;
        }

        $basic = $data['basic_info'] ?? [];
        $health = $data['health_summary'] ?? [];
        $summary = $data['conversation_summary'] ?? [];
        $name = (string) ($basic['name'] ?? 'ผู้รับบริการ');
        $messages = collect(data_get($data, 'messages.items', []))->reverse()->map(fn ($message) => [
            'sender' => $message['sender_name'] ?? ($message['sender_type'] === 'patient' ? $name : 'REMI'),
            'time' => $this->timeOnly($message['sent_at'] ?? null),
            'body' => $message['message'] ?? '',
            'direction' => $message['sender_type'] === 'doctor' ? 'outgoing' : 'incoming',
            'tone' => $message['sender_type'] === 'doctor' ? 'info' : 'default',
        ])->values()->all();
        $clinicalParts = collect($health['recorded_complications'] ?? [])->pluck('label')->filter();

        if (!empty($health['history_medicine_raw']) && $health['history_medicine_raw'] !== 'ไม่มี') {
            $clinicalParts->push('ประวัติยา: ' . $health['history_medicine_raw']);
        }

        return [
            'id' => 'patient-' . $userId,
            'patient_user_id' => $userId,
            'source' => 'line',
            'line_conversation_id' => $userId,
            'type' => 'direct',
            'title' => $name,
            'initials' => mb_substr(preg_replace('/\s+/u', '', $name), 0, 2),
            'subtitle' => 'ข้อความ 1:1 • LINE',
            'preview' => $summary['latest_message'] ?? 'ยังไม่มีข้อความ',
            'updated' => $this->shortDate($summary['latest_message_at'] ?? null),
            'unread' => (int) ($summary['unread_count'] ?? 0),
            'patient' => [
                'hn' => $this->displayHn($basic['hn'] ?? null),
                'age' => $basic['age'] ?? '-',
                'gestational_age' => isset($basic['gestational_week']) ? $basic['gestational_week'] . ' สัปดาห์' : '-',
                'clinical_summary' => $clinicalParts->isEmpty() ? 'ไม่มีข้อมูลภาวะแทรกซ้อนที่บันทึก' : $clinicalParts->join(' • '),
            ],
            'messages' => $messages,
            'pagination' => [
                'current_page' => (int) data_get($data, 'messages.current_page', 1),
                'last_page' => (int) data_get($data, 'messages.last_page', 1),
                'per_page' => (int) data_get($data, 'messages.per_page', $payload['per_page']),
                'total' => (int) data_get($data, 'messages.total', 0),
            ],
            'search' => $data['search'] ?? [],
        ];
    }

    public function chat(string $doctorId): array
    {
        $conversations = $this->conversations($doctorId) ?? [];

        return [
            'title' => 'ข้อความ',
            'subtitle' => 'เลือก Direct หรือกลุ่มเพื่อเริ่มสนทนา',
            'unread_count' => collect($conversations)->sum('unread'),
            'conversations' => collect($conversations)->map(fn ($conversation) => [
                'id' => $conversation['id'],
                'type' => $conversation['type'],
                'target_id' => $conversation['type'] === 'direct'
                    ? ($conversation['patient_user_id'] ?? $conversation['line_conversation_id'])
                    : 'doctor-patient-cohort',
                'title' => $conversation['title'],
                'initials' => $conversation['initials'],
                'subtitle' => $conversation['subtitle'],
                'preview' => $conversation['preview'],
                'updated' => $conversation['updated'],
                'unread' => $conversation['unread'],
            ])->values()->all(),
        ];
    }

    private function call(string $method, array $payload): ?array
    {
        try {
            $request = Request::create('/api/' . $method, 'POST', $payload);
            $response = app(RemiDataApiController::class)->{$method}($request);

            if ($response->getStatusCode() >= 400) {
                report(new \RuntimeException('REMI API ' . $method . ' returned ' . $response->getStatusCode()));
                return null;
            }

            $decoded = json_decode($response->getContent(), true);

            return ($decoded['success'] ?? false) ? ($decoded['data'] ?? []) : null;
        } catch (Throwable $exception) {
            report($exception);
            return null;
        }
    }

    private function healthSummaryTiles(array $summary, array $basic): array
    {
        return [
            ['label' => 'อาหาร', 'value' => number_format((int) data_get($summary, 'food.total', 0)) . ' มื้อ', 'detail' => $this->shortDate(data_get($summary, 'food.last_recorded_at')), 'tone' => 'yellow'],
            ['label' => 'วิตามิน', 'value' => number_format((int) data_get($summary, 'vitamin.total', 0)) . ' ครั้ง', 'detail' => $this->shortDate(data_get($summary, 'vitamin.last_recorded_at')), 'tone' => 'green'],
            ['label' => 'ออกกำลังกาย', 'value' => number_format((int) data_get($summary, 'exercise.total', 0)) . ' ครั้ง', 'detail' => $this->shortDate(data_get($summary, 'exercise.last_recorded_at')), 'tone' => 'blue'],
            ['label' => 'ลูกดิ้น', 'value' => number_format((int) data_get($summary, 'fetal_movement.total', 0)) . ' รายการ', 'detail' => $this->shortDate(data_get($summary, 'fetal_movement.last_recorded_at')), 'tone' => 'purple'],
            ['label' => 'แบบสอบถาม', 'value' => number_format((int) data_get($summary, 'questions.questionnaire_answers.total', 0)) . ' คำตอบ', 'detail' => 'ข้อมูลที่บันทึก', 'tone' => 'pink'],
            ['label' => 'น้ำหนัก', 'value' => number_format((float) ($basic['weight'] ?? 0), 1) . ' กก.', 'detail' => 'BMI ' . ($basic['bmi'] ?? '-'), 'tone' => 'green'],
        ];
    }

    private function followUps(array $detail): array
    {
        $issues = collect(data_get($detail, 'monitoring_issues.complications', []))->map(fn ($item) => [
            'title' => $this->complicationLabel($item['label'] ?? $item['type'] ?? null),
            'detail' => 'ข้อมูลจากประวัติผู้รับบริการ',
            'time' => 'ล่าสุด',
            'tone' => 'danger',
            'route' => ($item['type'] ?? null) === 'compli_diabete' ? 'patients.glucose' : 'patients.health',
        ]);
        $foodCount = (int) data_get($detail, 'monitoring_issues.food_reviews.total', 0);

        if ($foodCount > 0) {
            $issues->push(['title' => 'อาหารรอตรวจ', 'detail' => $foodCount . ' รายการ', 'time' => 'ล่าสุด', 'tone' => 'warning', 'route' => 'patients.nutrition']);
        }

        return $issues->values()->all();
    }

    private function healthContract(array $data): array
    {
        $weights = collect($data['weight_history'] ?? []);
        $exercises = collect(data_get($data, 'exercise_history.items', []));
        $fetal = collect(data_get($data, 'fetal_movement_history.items', []));
        $exerciseChart = $exercises->reverse()->values();
        $exerciseHistory = $exercises->sortByDesc(fn ($item) => trim(
            (string) ($item['date'] ?? '') . ' ' . (string) ($item['time'] ?? $item['recorded_at'] ?? '')
        ))->values();
        $exerciseDays = $exerciseChart->groupBy(fn ($item) => (string) (
            $item['date'] ?? substr((string) ($item['recorded_at'] ?? ''), 0, 10)
        ));
        $fetalChart = $fetal->reverse()->values();

        return [
            'weight' => [
                'labels' => $weights->map(fn ($item) => 'สัปดาห์ ' . ($item['gestational_week'] ?? '-'))->all(),
                'values' => $weights->map(fn ($item) => (float) ($item['weight'] ?? 0))->all(),
                'recommended_min' => $weights->map(fn ($item) => data_get($item, 'target_weight.min'))->all(),
                'recommended_max' => $weights->map(fn ($item) => data_get($item, 'target_weight.max'))->all(),
            ],
            'latest' => collect($data['recent_activities'] ?? [])->map(fn ($item) => [
                'time' => $this->shortDate($item['recorded_at'] ?? null),
                'label' => $this->activityLabel($item['activity_type'] ?? ''),
                'value' => ($item['value'] ?? '-') . $this->unitLabel($item['unit'] ?? null),
                'tone' => $this->activityTone($item['activity_type'] ?? ''),
                'icon' => $this->activityIcon($item['activity_type'] ?? ''),
            ])->values()->all(),
            'exercise' => [
                'labels' => $exerciseDays->keys()->map(fn ($date) => $this->thaiChartDate($date))->all(),
                'values' => $exerciseDays->map(fn ($items) => $items->sum(fn ($item) => (int) ($item['duration_minutes'] ?? 0)))->values()->all(),
                'light' => $this->exerciseMinutesByTone($exerciseDays, 'light'),
                'medium' => $this->exerciseMinutesByTone($exerciseDays, 'medium'),
                'high' => $this->exerciseMinutesByTone($exerciseDays, 'high'),
                'unknown' => $this->exerciseMinutesByTone($exerciseDays, 'unknown'),
                'history' => $exerciseHistory->map(fn ($item) => [
                    'time' => $this->thaiShortDate($item['date'] ?? $item['recorded_at'] ?? null, false)
                        . ' ' . (substr((string) ($item['time'] ?? ''), 0, 5)
                            ?: $this->timeOnly($item['recorded_at'] ?? null)),
                    'date_label' => $this->thaiShortDate($item['date'] ?? $item['recorded_at'] ?? null, false),
                    'time_label' => substr((string) ($item['time'] ?? ''), 0, 5)
                        ?: $this->timeOnly($item['recorded_at'] ?? null),
                    'type' => $item['type'] ?? $item['exercise_type'] ?? 'ออกกำลังกาย',
                    'duration' => ($item['duration_minutes'] ?? 0) . ' นาที',
                    'intensity' => $item['intensity'] ?? '-',
                    'intensity_tone' => $this->exerciseIntensityTone($item['intensity'] ?? null),
                ])->all(),
            ],
            'fetal' => [
                'labels' => $fetalChart->map(fn ($item) => $this->thaiChartDate($item['date'] ?? null))->all(),
                'values' => $fetalChart->map(fn ($item) => collect($item['periods'] ?? [])->sum('count'))->all(),
                'history' => $fetal->map(fn ($item) => ['date' => $this->thaiShortDate($item['date'] ?? null, false), 'value' => collect($item['periods'] ?? [])->sum('count') . ' ครั้ง'])->all(),
            ],
        ];
    }

    private function nutritionContract(array $data): array
    {
        $summary = $data['retrospective_summary'] ?? [];
        $target = $data['nutrition_targets'] ?? [];
        $meals = collect(data_get($data, 'meal_history.items', []));
        $daily = collect($summary['daily_totals'] ?? []);
        $average = $summary['average_per_recorded_day'] ?? [];
        $days = (int) data_get($data, 'selected_period.total_days', 0);
        $vitaminRecords = collect(data_get($data, 'vitamin_history.records.items', []));
        $vitaminEndDate = Carbon::parse(
            data_get($data, 'selected_period.end_date')
                ?? $vitaminRecords->pluck('date')->filter()->max()
                ?? now()
        )->startOfDay();
        $vitaminDays = collect(range(6, 0))
            ->map(fn ($offset) => $vitaminEndDate->copy()->subDays($offset)->toDateString());
        $vitaminRows = $vitaminRecords
            ->groupBy(fn ($record) => trim((string) ($record['raw_value'] ?? 'วิตามิน')) ?: 'วิตามิน')
            ->map(fn ($records, $label) => [
                'label' => $label,
                'days' => $vitaminDays->map(fn ($date) => $records->contains('date', $date))->all(),
            ])->values();
        $energyTarget = isset($target['energy_kcal']) && is_numeric($target['energy_kcal'])
            ? (float) $target['energy_kcal']
            : null;
        $averageEnergy = isset($average['energy_kcal']) && is_numeric($average['energy_kcal'])
            ? (float) $average['energy_kcal']
            : null;
        $energyDeficit = $energyTarget === null
            ? null
            : max(0, ($energyTarget * $daily->count()) - (float) $daily->sum('energy_kcal'));
        $insights = collect();

        if ($daily->isEmpty()) {
            $insights->push([
                'title' => 'ยังไม่มีมื้ออาหารที่ AI วิเคราะห์เสร็จ',
                'detail' => 'ไม่พบข้อมูลที่นำมาคำนวณได้ในช่วงวันที่เลือก',
                'tone' => 'muted',
            ]);
        } else {
            $insights->push([
                'title' => 'มีข้อมูลอาหารที่วิเคราะห์แล้ว ' . number_format($daily->count()) . ' วัน',
                'detail' => 'จากช่วงที่เลือกทั้งหมด ' . number_format($days) . ' วัน',
                'tone' => 'blue',
            ]);
        }

        if ($energyTarget !== null && $averageEnergy !== null) {
            $difference = round($averageEnergy - $energyTarget);
            $insights->push([
                'title' => $difference < 0
                    ? 'พลังงานเฉลี่ยต่ำกว่าเป้าหมาย'
                    : ($difference > 0 ? 'พลังงานเฉลี่ยสูงกว่าเป้าหมาย' : 'พลังงานเฉลี่ยตรงตามเป้าหมาย'),
                'detail' => $difference === 0
                    ? number_format($averageEnergy) . ' kcal/วัน'
                    : 'ต่างจากเป้าหมาย ' . number_format(abs($difference)) . ' kcal/วัน',
                'tone' => $difference === 0 ? 'green' : 'yellow',
            ]);
        }

        $recordedVitaminDays = (int) data_get($data, 'vitamin_history.recorded_days', 0);
        $insights->push([
            'title' => 'บันทึกวิตามิน ' . number_format($recordedVitaminDays) . ' วัน',
            'detail' => 'ไม่มีบันทึกไม่ได้หมายความว่าไม่ได้รับประทาน',
            'tone' => $recordedVitaminDays > 0 ? 'green' : 'muted',
        ]);

        return [
            'summary' => [
                ['label' => 'พลังงานที่เหมาะสมเฉลี่ย', 'value' => $energyTarget === null ? 'ไม่มีข้อมูล' : number_format($energyTarget) . ' kcal/วัน', 'detail' => number_format($days) . ' วันในช่วงที่เลือก', 'tone' => 'pink'],
                ['label' => 'บริโภคเฉลี่ย', 'value' => $averageEnergy === null ? 'ไม่มีข้อมูล' : number_format($averageEnergy) . ' kcal/วัน', 'detail' => 'คำนวณจากวันที่มีข้อมูล', 'tone' => 'green'],
                ['label' => 'วันที่พลังงานถึงเป้าหมาย', 'value' => ($summary['days_reaching_energy_target'] ?? null) === null ? 'ยังไม่กำหนดเกณฑ์' : number_format((int) $summary['days_reaching_energy_target']) . ' วัน', 'detail' => 'จากข้อมูลที่วิเคราะห์แล้ว', 'tone' => 'blue'],
                ['label' => 'มื้ออาหารทั้งหมด', 'value' => number_format((int) ($summary['total_meals'] ?? $meals->count())) . ' มื้อ', 'detail' => 'วิเคราะห์แล้ว ' . number_format((int) ($summary['analyzed_meals'] ?? 0)), 'tone' => 'yellow'],
            ],
            'insights' => $insights->all(),
            'energy' => [
                'labels' => $daily->map(fn ($item) => $this->thaiShortDate($item['meal_date'] ?? null, false))->all(),
                'carbs' => $daily->map(fn ($item) => (float) ($item['carbohydrate_g'] ?? 0) * 4)->all(),
                'protein' => $daily->map(fn ($item) => (float) ($item['protein_g'] ?? 0) * 4)->all(),
                'fat' => $daily->map(fn ($item) => (float) ($item['fat_g'] ?? 0) * 9)->all(),
                'target' => $energyTarget,
                'deficit' => $energyDeficit,
            ],
            'nutrients' => collect(['carbohydrate' => 'คาร์โบไฮเดรต', 'protein' => 'โปรตีน', 'fat' => 'ไขมัน', 'fiber' => 'ใยอาหาร'])->map(function ($label, $key) use ($average, $target) {
                $targetValue = isset($target[$key . '_g']) && is_numeric($target[$key . '_g'])
                    ? (float) $target[$key . '_g']
                    : null;

                return [
                    'label' => $label,
                    'value' => (float) ($average[$key . '_g'] ?? 0),
                    'target' => $targetValue,
                    'progress' => $targetValue && $targetValue > 0
                        ? min(100, round((float) ($average[$key . '_g'] ?? 0) / $targetValue * 100))
                        : 0,
                    'unit' => 'g',
                    'tone' => $key === 'protein' ? 'green' : ($key === 'fiber' ? 'purple' : 'orange'),
                ];
            })->values()->all(),
            'vitamins' => $vitaminRows->all(),
            'vitamin_days' => $vitaminDays->map(fn ($date) => [
                'day' => Carbon::parse($date)->format('j'),
                'month' => Carbon::parse($date)->locale('th')->translatedFormat('M'),
            ])->all(),
            'vitamin_warning' => $vitaminRecords->isEmpty()
                ? 'ยังไม่มีข้อมูลวิตามินในช่วงที่เลือก'
                : 'มีการบันทึกวิตามิน ' . number_format((int) data_get($data, 'vitamin_history.recorded_days', 0)) . ' วัน',
            'meals' => $meals->map(function ($meal) {
                $mealPresentation = $this->mealPresentation($meal['meal'] ?? null, $meal['time'] ?? null);

                return [
                    'id' => $meal['id'] ?? null,
                    'date' => $this->shortDate($meal['date'] ?? null),
                    'meal' => $mealPresentation['label'],
                    'time' => substr((string) ($meal['time'] ?? ''), 0, 5),
                    'image_url' => $meal['image_url'] ?? null,
                    'food' => collect($meal['foods'] ?? [])->pluck('name')->filter()->join(' + ') ?: 'ไม่ระบุรายการ',
                    'energy' => number_format((float) ($meal['energy_kcal'] ?? 0)) . ' kcal',
                    'recommendation' => $meal['recommendation'] ?? 'ยังไม่มีคำแนะนำ',
                    'status' => $this->reviewLabel($meal['review_status'] ?? null),
                    'tone' => ($meal['review_status'] ?? null) === 'pending' ? 'warning' : 'success',
                    'meal_tone' => $mealPresentation['tone'],
                    'is_pending' => ($meal['review_status'] ?? null) === 'pending',
                    'review_href' => !empty($meal['id']) ? route('nutrition-review.index', ['meal_id' => $meal['id']]) : null,
                ];
            })->values()->all(),
            'meal_count' => (int) data_get($data, 'meal_history.total', $meals->count()),
        ];
    }

    private function glucoseChart(array $data): array
    {
        $points = collect(data_get($data, 'trend_and_summary.graph_data', []));
        $labels = $points->map(fn ($item) => substr((string) ($item['datetime'] ?? ''), 0, 10))->unique()->values();

        $periodLabels = [
            'before_meal' => 'ก่อนอาหาร',
            'after_meal_1h' => 'หลังอาหาร 1 ชั่วโมง',
            'after_meal_2h' => 'หลังอาหาร 2 ชั่วโมง',
            'bedtime' => 'ก่อนนอน',
        ];

        return [
            'labels' => $labels->map(fn ($date) => $this->thaiShortDate($date, false))->all(),
            'readings' => $points->map(function ($item) use ($labels, $periodLabels) {
                $date = substr((string) ($item['datetime'] ?? ''), 0, 10);
                $period = $item['series'] ?? 'after_meal_1h';

                return [
                    'x' => $labels->search($date),
                    'value' => (float) ($item['value'] ?? 0),
                    'type' => $period,
                    'status' => $item['status'] ?? 'unknown',
                    'period_label' => $periodLabels[$period] ?? 'ไม่ทราบช่วงตรวจ',
                    'meal' => $item['meal'] ?? null,
                ];
            })->values()->all(),
        ];
    }

    private function glucoseContract(array $data): array
    {
        $overall = data_get($data, 'trend_and_summary.overall', []);
        $before = data_get($data, 'trend_and_summary.by_period.before_meal', []);
        $after = data_get($data, 'trend_and_summary.after_meal_combined', []);
        $insulin = collect(data_get($data, 'current_insulin.items', []))->first() ?? [];
        $insulinHistory = collect($data['medication_adjustment_history'] ?? []);
        $groups = collect($data['blood_sugar_by_meal_period'] ?? []);
        $bloodSugarHistory = collect($data['blood_sugar_history'] ?? []);
        $targetMinimums = $bloodSugarHistory->pluck('target.min')->filter(fn ($value) => is_numeric($value));
        $targetMaximums = $bloodSugarHistory->pluck('target.max')->filter(fn ($value) => is_numeric($value));
        $targetRange = $targetMinimums->isNotEmpty() && $targetMaximums->isNotEmpty()
            ? number_format((float) $targetMinimums->min()) . '–' . number_format((float) $targetMaximums->max())
            : null;

        return [
            'summary' => [
                ['label' => 'น้ำตาลก่อนอาหาร', 'value' => isset($before['average_mg_dl']) ? $before['average_mg_dl'] . ' mg/dL' : 'ไม่มีข้อมูล', 'detail' => 'ค่าเฉลี่ยในช่วงที่เลือก', 'tone' => 'green'],
                ['label' => 'หลังอาหาร', 'value' => isset($after['average_mg_dl']) ? $after['average_mg_dl'] . ' mg/dL' : 'ไม่มีข้อมูล', 'detail' => 'ค่าเฉลี่ยหลังอาหาร', 'tone' => 'pink'],
                ['label' => 'อยู่ในเป้าหมาย', 'value' => (data_get($overall, 'normal.percentage') ?? 0) . '%', 'detail' => number_format((int) ($overall['classified_total'] ?? 0)) . ' ครั้งที่จำแนกได้', 'tone' => 'blue'],
                ['label' => 'Insulin ปัจจุบัน', 'value' => isset($insulin['dose_units']) ? $insulin['dose_units'] . ' units' : 'ไม่มีข้อมูล', 'detail' => $this->insulinTypeLabel($insulin['type'] ?? null), 'tone' => 'yellow'],
            ],
            'issues' => $bloodSugarHistory->where('status', 'high')->take(3)->map(fn ($item) => ['title' => 'ค่าน้ำตาลสูงกว่าเป้าหมาย', 'detail' => ($item['value'] ?? '-') . ' mg/dL • ' . ($item['period_label'] ?? ''), 'tone' => 'pink'])->values()->all(),
            'high_count' => $bloodSugarHistory->where('status', 'high')->count(),
            'target_range' => $targetRange,
            'unit' => 'mg/dL',
            'records' => $bloodSugarHistory->map(fn ($item) => [
                'date' => $this->shortDate(($item['date'] ?? '') . ' ' . ($item['time'] ?? '')),
                'period' => ($item['meal'] ?? '') . ' ' . ($item['period_label'] ?? ''),
                'value' => ($item['value'] ?? '-') . ' mg/dL',
                'target' => (data_get($item, 'target.min') ?? '-') . '–' . (data_get($item, 'target.max') ?? '-') . ' mg/dL',
                'status' => $item['status_label'] ?? '-',
                'tone' => ($item['status'] ?? null) === 'high' ? 'danger' : (($item['status'] ?? null) === 'low' ? 'warning' : 'success'),
            ])->values()->all(),
            'chart_summary' => [
                'labels' => $groups->pluck('label')->all(),
                'low' => $groups->map(fn ($item) => (float) data_get($item, 'low.percentage', 0))->all(),
                'normal' => $groups->map(fn ($item) => (float) data_get($item, 'normal.percentage', 0))->all(),
                'high' => $groups->map(fn ($item) => (float) data_get($item, 'high.percentage', 0))->all(),
                'no_data' => $groups->map(fn ($item) => (int) ($item['total'] ?? 0) === 0 ? 100 : 0)->all(),
            ],
            'insulin' => [
                'updated_by' => $insulin['prescribed_by'] ?? '-',
                'updated_at' => $this->shortDate($insulin['start_date'] ?? null),
                'type' => $this->insulinTypeLabel($insulin['type'] ?? null),
                'time' => trim($this->injectionPeriodLabel($insulin['injection_period'] ?? null) . ' ' . substr((string) ($insulin['injection_time'] ?? ''), 0, 5)) ?: '-',
                'previous_dose' => data_get($insulinHistory->first(), 'old_dose_units', 0),
                'current_dose' => $insulin['dose_units'] ?? 0,
                'method' => $insulin['injection_method'] ?? 'ฉีดใต้ผิวหนัง',
                'reason' => data_get($insulinHistory->first(), 'reason') ?? $insulin['note'] ?? 'ไม่มีประวัติการปรับยา',
                'instruction' => data_get($insulinHistory->first(), 'recommendation') ?? $insulin['note'] ?? '-',
            ],
            'insulin_history' => $insulinHistory->map(fn ($item) => [
                'date' => $this->shortDate($item['recorded_at'] ?? null),
                'type' => $item['insulin_name'] ?? 'Insulin',
                'time' => substr((string) ($item['injection_time'] ?? ''), 0, 5) ?: '-',
                'previous_dose' => $item['old_dose_units'] ?? '-',
                'current_dose' => $item['new_dose_units'] ?? $item['stored_dose_units'] ?? '-',
                'reason' => $item['reason'] ?? $item['note'] ?? 'ไม่มีข้อมูลเหตุผลจาก API',
                'updated_by' => $item['prescribed_by'] ?? 'ไม่มีข้อมูลผู้ปรับจาก API',
            ])->values()->all(),
        ];
    }

    private function nutritionTotals(array $meal): array
    {
        return [
            ['label' => 'พลังงาน', 'value' => number_format((float) ($meal['energy_kcal'] ?? 0)) . ' kcal', 'tone' => 'pink'],
            ['label' => 'คาร์บ', 'value' => number_format((float) data_get($meal, 'nutrients.carbohydrate_g', 0)) . ' g', 'tone' => 'yellow'],
            ['label' => 'โปรตีน', 'value' => number_format((float) data_get($meal, 'nutrients.protein_g', 0)) . ' g', 'tone' => 'green'],
            ['label' => 'ไขมัน', 'value' => number_format((float) data_get($meal, 'nutrients.fat_g', 0)) . ' g', 'tone' => 'blue'],
            ['label' => 'ใยอาหาร', 'value' => number_format((float) data_get($meal, 'nutrients.fiber_g', 0)) . ' g', 'tone' => 'pink'],
        ];
    }

    private function thaiDate($value): string
    {
        if (!$value) return '-';
        $date = $value instanceof Carbon ? $value : Carbon::parse($value);
        return $date->locale('th')->translatedFormat('j F') . ' ' . ($date->year + 543);
    }

    private function thaiDateParts($value): array
    {
        if (!$value) return ['date' => '-', 'year' => '-'];
        $date = Carbon::parse($value)->locale('th');
        return ['date' => $date->translatedFormat('j M'), 'year' => (string) ($date->year + 543)];
    }

    private function shortDate($value): string
    {
        return $this->thaiShortDate($value);
    }

    private function thaiShortDate($value, bool $includeTime = true): string
    {
        if (!$value) return '-';

        try {
            $date = Carbon::parse($value)->locale('th');
            $formatted = $date->translatedFormat('j M') . ' ' . ($date->year + 543);
            $hasExplicitTime = !preg_match('/^\d{4}-\d{2}-\d{2}$/', trim((string) $value));

            return $includeTime && $hasExplicitTime
                ? $formatted . ' ' . $date->format('H:i')
                : $formatted;
        } catch (Throwable) {
            return (string) $value;
        }
    }

    private function thaiChartDate($value): string
    {
        if (!$value) return '-';
        try { return Carbon::parse($value)->locale('th')->translatedFormat('j M'); } catch (Throwable) { return (string) $value; }
    }

    private function timeOnly($value): string
    {
        if (!$value) return '-';
        try { return Carbon::parse($value)->format('H:i'); } catch (Throwable) { return '-'; }
    }

    private function displayHn($value): string { return (!$value || strtoupper((string) $value) === 'NULL') ? '-' : (string) $value; }
    private function weightColor(int $code): string { return [0 => '#a3979b', 1 => '#34aa80', 2 => '#4e79e8', 3 => '#f07a2b', 4 => '#df476d'][$code] ?? '#a3979b'; }
    private function weightTone(int $code): string { return [0 => 'muted', 1 => 'success', 2 => 'info', 3 => 'warning', 4 => 'danger'][$code] ?? 'muted'; }
    private function weightLabel(int $code): string { return [0 => 'ไม่มีการบันทึก', 1 => 'น้ำหนักปกติ', 2 => 'น้ำหนักต่ำกว่าเกณฑ์', 3 => 'น้ำหนักเกินเกณฑ์', 4 => 'ภาวะแทรกซ้อน'][$code] ?? 'ไม่ทราบสถานะ'; }
    private function activityLabel(string $type): string { return ['weight' => 'น้ำหนัก', 'exercise' => 'ออกกำลังกาย', 'fetal_movement' => 'ลูกดิ้น', 'food' => 'อาหาร', 'vitamin' => 'วิตามิน'][$type] ?? $type; }
    private function activityTone(string $type): string { return ['weight' => 'pink', 'exercise' => 'blue', 'fetal_movement' => 'orange', 'food' => 'green', 'vitamin' => 'purple'][$type] ?? 'blue'; }
    private function activityIcon(string $type): string { return ['weight' => 'fa-weight-scale', 'exercise' => 'fa-person-walking', 'fetal_movement' => 'fa-baby', 'food' => 'fa-bowl-food', 'vitamin' => 'fa-capsules'][$type] ?? 'fa-circle-info'; }
    private function exerciseIntensityTone($intensity): string
    {
        $value = mb_strtolower(trim((string) $intensity), 'UTF-8');

        if (str_contains($value, 'หนัก') || in_array($value, ['high', 'hard', 'vigorous'], true)) return 'high';
        if (str_contains($value, 'ปานกลาง') || in_array($value, ['medium', 'moderate'], true)) return 'medium';
        if (str_contains($value, 'เบา') || in_array($value, ['low', 'light'], true)) return 'light';

        return 'unknown';
    }
    private function exerciseMinutesByTone($exerciseDays, string $tone): array
    {
        return $exerciseDays->map(fn ($items) => $items
            ->filter(fn ($item) => $this->exerciseIntensityTone($item['intensity'] ?? null) === $tone)
            ->sum(fn ($item) => (int) ($item['duration_minutes'] ?? 0)))
            ->values()
            ->all();
    }
    private function unitLabel($unit): string { return ['kg' => ' กก.', 'minutes' => ' นาที', 'times' => ' ครั้ง', 'kcal' => ' kcal'][$unit] ?? ''; }
    private function mealLabel($meal): string { return ['breakfast' => 'เช้า', 'lunch' => 'กลางวัน', 'dinner' => 'เย็น', 'snack' => 'ว่าง'][$meal] ?? ($meal ?: '-'); }
    private function mealPresentation($meal, $time): array
    {
        $meal = strtolower(trim((string) $meal));
        $hour = is_string($time) && preg_match('/^(\d{1,2}):/', $time, $matches) ? (int) $matches[1] : null;

        return match ($meal) {
            'breakfast' => ['label' => 'เช้า', 'tone' => 'breakfast'],
            'lunch' => ['label' => 'กลางวัน', 'tone' => 'lunch'],
            'dinner' => ['label' => 'เย็น', 'tone' => 'dinner'],
            'dessert_lu', 'morning_snack', 'snack_morning' => ['label' => 'ว่างเช้า', 'tone' => 'snack-morning'],
            'dessert_din', 'afternoon_snack', 'snack_afternoon' => ['label' => 'ว่างเย็น', 'tone' => 'snack-afternoon'],
            'snack' => $hour !== null && $hour < 12
                ? ['label' => 'ว่างเช้า', 'tone' => 'snack-morning']
                : ['label' => 'ว่างเย็น', 'tone' => 'snack-afternoon'],
            default => ['label' => $this->mealLabel($meal), 'tone' => $meal ?: 'breakfast'],
        };
    }
    private function reviewLabel($status): string { return ['pending' => 'รอตรวจ', 'reviewing' => 'กำลังตรวจ', 'reviewed' => 'ตรวจแล้ว', 'approved' => 'ตรวจแล้ว', 'completed' => 'ตรวจแล้ว', 'rejected' => 'ส่งกลับแก้ไข'][$status] ?? ($status ?: '-'); }
    private function confidenceTone(float $score): string
    {
        return match (true) {
            $score >= 80 => 'success',
            $score >= 50 => 'warning',
            default => 'danger',
        };
    }
    private function carePlanStatusLabel($status): string { return ['active' => 'กำลังใช้งาน', 'completed' => 'เสร็จสิ้น', 'cancelled' => 'ยกเลิก'][$status] ?? ($status ?: '-'); }
    private function complicationLabel($type): string { return ['compli_diabete' => 'เบาหวาน', 'compli_hypertension' => 'ความดันโลหิตสูง', 'compli_preterm_birth' => 'ความเสี่ยงคลอดก่อนกำหนด'][$type] ?? ($type ?: 'ภาวะแทรกซ้อน'); }
    private function insulinTypeLabel($type): string { return ['rapid' => 'ออกฤทธิ์เร็ว', 'short' => 'ออกฤทธิ์สั้น', 'intermediate' => 'ออกฤทธิ์ปานกลาง', 'long' => 'ออกฤทธิ์ยาว', 'premixed' => 'ชนิดผสม'][$type] ?? ($type ?: 'ไม่มีข้อมูล'); }
    private function injectionPeriodLabel($period): string { return ['before_breakfast' => 'ก่อนอาหารเช้า', 'before_lunch' => 'ก่อนอาหารกลางวัน', 'before_dinner' => 'ก่อนอาหารเย็น', 'bedtime' => 'ก่อนนอน'][$period] ?? ($period ?: ''); }
}
