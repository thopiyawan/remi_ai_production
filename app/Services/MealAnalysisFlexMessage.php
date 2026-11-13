<?php

namespace App\Services;

class MealAnalysisFlexMessage
{
    public function build(array $data): array
    {
        $nutrition = is_array($data['nutrition'] ?? null) ? $data['nutrition'] : [];
        $analysis = is_array($data['analysis'] ?? null) ? $data['analysis'] : [];
        $theme = $this->riskTheme(
            (string) ($analysis['risk_level'] ?? ''),
            (string) ($data['meal_risk_level'] ?? '')
        );

        $mealName = $this->text($data['full_foodname'] ?? null, 'ไม่ทราบชื่ออาหาร', 180);
        $mealType = $this->mealTypeLabel($data['meal_type'] ?? null);
        $weight = $this->number($nutrition['portion_estimation'] ?? 0);
        $calories = $this->number($nutrition['calories'] ?? 0);
        $analysisText = $this->text(
            $analysis['message'] ?? null,
            'ยังไม่มีรายละเอียดการวิเคราะห์',
            700
        );
        $recommendations = $this->recommendations($data['recommendations'] ?? []);

        return [
            'type' => 'bubble',
            'size' => 'giga',
            'header' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => "🍜 {$mealName}",
                        'size' => 'xl',
                        'weight' => 'bold',
                        'color' => '#FFFFFF',
                        'wrap' => true,
                        'margin' => 'lg',
                    ],
                    [
                        'type' => 'box',
                        'layout' => 'horizontal',
                        'contents' => [
                            [
                                'type' => 'text',
                                'text' => "{$weight} g  •  {$calories} kcal",
                                'size' => 'sm',
                                'weight' => 'bold',
                                'color' => '#FFFFFF',
                                'flex' => 1,
                            ],
                            [
                                'type' => 'text',
                                'text' => $theme['header_label'],
                                'size' => 'sm',
                                'weight' => 'bold',
                                'color' => '#FFFFFF',
                                'align' => 'end',
                            ],
                        ],
                        'margin' => 'md',
                    ],
                ],
                'paddingAll' => '20px',
                'backgroundColor' => $theme['header_color'],
            ],
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => $mealType,
                        'size' => 'xs',
                        'color' => '#806C74',
                    ],
                    [
                        'type' => 'text',
                        'text' => 'คุณค่าทางโภชนาการ',
                        'size' => 'md',
                        'weight' => 'bold',
                        'color' => '#33272C',
                    ],
                    [
                        'type' => 'box',
                        'layout' => 'horizontal',
                        'contents' => [
                            $this->nutrientBox('คาร์บ', $nutrition['carb'] ?? 0, '#F08A5D', '#FFF4E8'),
                            $this->nutrientBox('โปรตีน', $nutrition['protein'] ?? 0, '#6F9B55', '#F1F8EC'),
                            $this->nutrientBox('ไขมัน', $nutrition['fat'] ?? 0, '#EF6C98', '#FFF1F5'),
                            $this->nutrientBox('ใยอาหาร', $nutrition['fiber'] ?? 0, '#48948B', '#EEF8F7'),
                        ],
                        'spacing' => 'sm',
                    ],
                    [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'contents' => [
                            [
                                'type' => 'box',
                                'layout' => 'horizontal',
                                'contents' => [
                                    [
                                        'type' => 'text',
                                        'text' => 'ผลวิเคราะห์ GDM',
                                        'size' => 'md',
                                        'weight' => 'bold',
                                        'color' => '#5A4534',
                                        'flex' => 1,
                                    ],
                                    [
                                        'type' => 'text',
                                        'text' => $theme['risk_label'],
                                        'size' => 'xs',
                                        'weight' => 'bold',
                                        'color' => $theme['risk_color'],
                                        'align' => 'end',
                                        'flex' => 0,
                                    ],
                                ],
                            ],
                            [
                                'type' => 'text',
                                'text' => $analysisText,
                                'size' => 'sm',
                                'color' => '#6E5947',
                                'wrap' => true,
                                'lineSpacing' => '4px',
                            ],
                        ],
                        'backgroundColor' => $theme['analysis_background'],
                        'cornerRadius' => '16px',
                        'paddingAll' => '15px',
                        'spacing' => 'sm',
                    ],
                    [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'contents' => [
                            [
                                'type' => 'text',
                                'text' => '💡 คำแนะนำ',
                                'size' => 'md',
                                'weight' => 'bold',
                                'color' => '#8E3152',
                            ],
                            [
                                'type' => 'text',
                                'text' => implode("\n", array_map(fn (string $item) => "• {$item}", $recommendations)),
                                'size' => 'sm',
                                'color' => '#614D55',
                                'wrap' => true,
                                'lineSpacing' => '5px',
                                'margin' => 'sm',
                            ],
                        ],
                        'backgroundColor' => '#FFF1F5',
                        'cornerRadius' => '14px',
                        'paddingAll' => '14px',
                    ],
                ],
                'paddingAll' => '20px',
                'spacing' => 'lg',
                'backgroundColor' => '#FFFCFD',
            ],
            'footer' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [
                    [
                        'type' => 'box',
                        'layout' => 'horizontal',
                        'contents' => [
                            [
                                'type' => 'button',
                                'style' => 'primary',
                                'height' => 'sm',
                                'color' => '#D94F7C',
                                'action' => [
                                    'type' => 'message',
                                    'label' => 'บันทึกมื้อนี้',
                                    'text' => 'บันทึกมื้อนี้',
                                ],
                            ],
                            [
                                'type' => 'button',
                                'style' => 'secondary',
                                'height' => 'sm',
                                'color' => '#F8DCE6',
                                'action' => [
                                    'type' => 'message',
                                    'label' => 'วิเคราะห์ใหม่',
                                    'text' => 'วิเคราะห์รูปใหม่',
                                ],
                            ],
                        ],
                        'spacing' => 'sm',
                    ],
                ],
                'paddingAll' => '16px',
                'spacing' => 'sm',
            ],
        ];
    }

    private function nutrientBox(string $label, mixed $value, string $color, string $background): array
    {
        return [
            'type' => 'box',
            'layout' => 'vertical',
            'contents' => [
                ['type' => 'text', 'text' => $label, 'size' => 'xxs', 'color' => '#806C74'],
                [
                    'type' => 'text',
                    'text' => $this->number($value) . ' g',
                    'size' => 'md',
                    'weight' => 'bold',
                    'color' => $color,
                    'margin' => 'xs',
                ],
            ],
            'backgroundColor' => $background,
            'cornerRadius' => '12px',
            'paddingAll' => '10px',
            'flex' => 1,
        ];
    }

    private function riskTheme(string $risk, string $label): array
    {
        $value = mb_strtolower(trim("{$risk} {$label}"));

        if (str_contains($value, 'high') || str_contains($value, 'ควรระวัง') || str_contains($value, 'เสี่ยงสูง')) {
            return [
                'header_label' => '⚠️ ควรระวัง',
                'risk_label' => 'เสี่ยงสูง',
                'header_color' => '#D94F7C',
                'risk_color' => '#B76A21',
                'analysis_background' => '#FFF8E8',
            ];
        }

        if (str_contains($value, 'medium') || str_contains($value, 'พอใช้') || str_contains($value, 'ปานกลาง')) {
            return [
                'header_label' => '🟡 พอใช้',
                'risk_label' => 'เสี่ยงปานกลาง',
                'header_color' => '#D68B2C',
                'risk_color' => '#A26716',
                'analysis_background' => '#FFF8E8',
            ];
        }

        return [
            'header_label' => '✅ ดี',
            'risk_label' => 'เสี่ยงต่ำ',
            'header_color' => '#48948B',
            'risk_color' => '#36766F',
            'analysis_background' => '#EEF8F7',
        ];
    }

    private function mealTypeLabel(mixed $mealType): string
    {
        $value = mb_strtolower(trim((string) $mealType));

        return match ($value) {
            'breakfast', 'เช้า', 'อาหารเช้า', 'มื้อเช้า' => 'มื้อเช้า',
            'lunch', 'กลางวัน', 'อาหารกลางวัน', 'มื้อกลางวัน' => 'มื้อกลางวัน',
            'dinner', 'เย็น', 'อาหารเย็น', 'มื้อเย็น' => 'มื้อเย็น',
            'snack', 'อาหารว่าง', 'มื้อว่าง' => 'มื้อว่าง',
            default => $this->text($mealType, 'มื้ออาหาร', 40),
        };
    }

    private function recommendations(mixed $items): array
    {
        if (!is_array($items)) {
            return ['ยังไม่มีคำแนะนำเพิ่มเติม'];
        }

        $result = [];
        foreach (array_slice($items, 0, 5) as $item) {
            if (!is_scalar($item)) {
                continue;
            }

            $text = $this->text($item, '', 220);
            if ($text !== '') {
                $result[] = ltrim($text, "• \t\n\r\0\x0B");
            }
        }

        return $result ?: ['ยังไม่มีคำแนะนำเพิ่มเติม'];
    }

    private function number(mixed $value): string
    {
        if (is_string($value)) {
            $value = preg_replace('/[^0-9.\-]/', '', $value);
        }

        $number = is_numeric($value) ? max(0, (float) $value) : 0;

        return number_format($number, $number == floor($number) ? 0 : 1, '.', ',');
    }

    private function text(mixed $value, string $fallback, int $limit): string
    {
        $text = is_scalar($value) ? trim((string) $value) : '';
        $text = preg_replace('/\s+/u', ' ', $text) ?: '';

        return $text === '' ? $fallback : mb_substr($text, 0, $limit);
    }
}
