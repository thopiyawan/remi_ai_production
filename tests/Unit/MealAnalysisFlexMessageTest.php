<?php

namespace Tests\Unit;

use App\Services\MealAnalysisFlexMessage;
use PHPUnit\Framework\TestCase;

class MealAnalysisFlexMessageTest extends TestCase
{
    public function test_it_maps_ai_analysis_to_the_selected_flex_layout(): void
    {
        $message = (new MealAnalysisFlexMessage())->build([
            'meal_type' => 'lunch',
            'full_foodname' => 'ขนมจีนน้ำยาตีนไก่',
            'meal_risk_level' => 'ควรระวัง',
            'nutrition' => [
                'calories' => 400,
                'carb' => 60,
                'protein' => 15,
                'fat' => 20,
                'fiber' => 3,
                'portion_estimation' => 350,
            ],
            'analysis' => [
                'risk_level' => 'high',
                'message' => 'คาร์โบไฮเดรตสูง อาจทำให้น้ำตาลหลังอาหารสูง',
            ],
            'recommendations' => ['เพิ่มผักครึ่งจาน', 'ลดปริมาณเส้น'],
        ]);

        $this->assertSame('bubble', $message['type']);
        $this->assertSame('🍜 ขนมจีนน้ำยาตีนไก่', $message['header']['contents'][0]['text']);
        $this->assertSame('350 g  •  400 kcal', $message['header']['contents'][1]['contents'][0]['text']);
        $this->assertSame('⚠️ ควรระวัง', $message['header']['contents'][1]['contents'][1]['text']);
        $this->assertSame('มื้อกลางวัน', $message['body']['contents'][0]['text']);
        $this->assertSame('60 g', $message['body']['contents'][2]['contents'][0]['contents'][1]['text']);
        $this->assertSame('เสี่ยงสูง', $message['body']['contents'][3]['contents'][0]['contents'][1]['text']);
        $this->assertSame("• เพิ่มผักครึ่งจาน\n• ลดปริมาณเส้น", $message['body']['contents'][4]['contents'][1]['text']);
    }

    public function test_it_produces_safe_fallback_content_for_incomplete_ai_data(): void
    {
        $message = (new MealAnalysisFlexMessage())->build([
            'nutrition' => ['calories' => 'ประมาณ 420 kcal'],
            'recommendations' => 'not-an-array',
        ]);

        $this->assertSame('🍜 ไม่ทราบชื่ออาหาร', $message['header']['contents'][0]['text']);
        $this->assertSame('0 g  •  420 kcal', $message['header']['contents'][1]['contents'][0]['text']);
        $this->assertSame('✅ ดี', $message['header']['contents'][1]['contents'][1]['text']);
        $this->assertSame('• ยังไม่มีคำแนะนำเพิ่มเติม', $message['body']['contents'][4]['contents'][1]['text']);
    }
}
