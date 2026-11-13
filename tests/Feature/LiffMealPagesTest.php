<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LiffMealPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_meal_history_and_edit_pages_render_existing_liff_data(): void
    {
        $activityId = $this->createMeal();

        $this->get('/food_diary/line-user?date=2026-08-31')
            ->assertOk()
            ->assertSee('ประวัติการรับประทานอาหาร')
            ->assertSee('ขนมจีนน้ำยาตีนไก่')
            ->assertSee('400 kcal')
            ->assertSee('/record_diary/line-user?meal=2&amp;date=2026-08-31', false);

        $this->get("/food_diary/line-user/{$activityId}/edit")
            ->assertOk()
            ->assertSee('แก้ไขมื้ออาหาร')
            ->assertSee('ขนมจีนน้ำยาตีนไก่');
    }

    public function test_meal_edit_updates_the_existing_tracker_records(): void
    {
        $activityId = $this->createMeal();

        $this->patch("/food_diary/line-user/{$activityId}", [
            'food_name' => 'ข้าวต้มปลา',
            'time' => '13:15',
            'portion' => 320,
            'unit' => 'กรัม',
            'calorie' => 360,
            'carbohydrate' => 52,
            'protein' => 22,
            'fat' => 8,
            'fiber' => 4,
        ])->assertRedirect('/food_diary/line-user?date=2026-08-31');

        $this->assertDatabaseHas('tracker', [
            'user_id' => 'line-user',
            'lunch' => 'ข้าวต้มปลา',
            'time_lunch' => '13:15',
        ]);
        $this->assertDatabaseHas('tracker_activity', [
            'id' => $activityId,
            'calorie' => 360,
            'protein' => 22,
        ]);
    }

    public function test_original_add_form_uses_the_selected_meal_and_date(): void
    {
        $this->createMeal();

        $this->get('/record_diary/line-user?meal=4&date=2026-08-31')
            ->assertOk()
            ->assertSee('id="getDate" name="date"', false)
            ->assertSee('value="2026-08-31"', false)
            ->assertSee('id="meal" name="meal" value="4"', false)
            ->assertSee('group-selected active" value="4"', false);
    }

    public function test_original_add_form_can_create_a_meal_on_a_new_date(): void
    {
        $this->post('/save_diary', [
            'user_id' => 'line-user',
            'date' => '2026-09-01',
            'meal' => 4,
            'time' => '10:00',
            'mainfood_name' => 'นมจืด',
            'moreFields' => [
                ['food_name' => 'นมจืด', 'portion' => 200, 'unit' => 7],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('tracker', [
            'user_id' => 'line-user',
            'date' => '2026-09-01',
            'dessert_lu' => 'นมจืด',
            'dessert_din' => 'NULL',
        ]);
        $this->assertDatabaseHas('tracker_activity', [
            'user_id' => 'line-user',
            'date' => '2026-09-01',
            'meal' => '4',
            'food_name' => 'นมจืด',
        ]);
    }

    private function createMeal(): int
    {
        $trackerId = DB::table('tracker')->insertGetId([
            'user_id' => 'line-user',
            'breakfast' => 'NULL',
            'lunch' => 'ขนมจีนน้ำยาตีนไก่',
            'dinner' => 'NULL',
            'dessert_lu' => 'NULL',
            'dessert_din' => 'NULL',
            'exercise' => '',
            'vitamin' => '0',
            'date' => '2026-08-31',
            'time_breakfast' => '',
            'time_lunch' => '12:30',
            'time_dinner' => '',
            'data_to_ulife' => '0',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('tracker_activity')->insertGetId([
            'user_id' => 'line-user',
            'food_id' => $trackerId,
            'date' => '2026-08-31',
            'time' => '12:30',
            'meal' => 2,
            'food_name' => 'เส้นขนมจีนและไก่',
            'portion' => 350,
            'unit' => 'กรัม',
            'calorie' => 400,
            'carbohydrate' => 60,
            'protein' => 15,
            'fat' => 20,
            'fiber' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
