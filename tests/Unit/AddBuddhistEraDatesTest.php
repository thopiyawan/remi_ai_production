<?php

namespace Tests\Unit;

use App\Http\Middleware\AddBuddhistEraDates;
use Illuminate\Http\Request;
use Tests\TestCase;

class AddBuddhistEraDatesTest extends TestCase
{
    public function test_it_adds_buddhist_era_companion_fields_recursively(): void
    {
        $middleware = new AddBuddhistEraDates();
        $request = Request::create('/api/example', 'POST');

        $response = $middleware->handle($request, fn () => response()->json([
            'date' => '2026-10-11',
            'datetime' => '2026-10-11T08:30:00+07:00',
            'recorded_at' => '2026-10-11 08:30:00',
            'items' => [
                ['due_date' => '2027-01-09', 'label' => 'ตัวอย่าง'],
            ],
        ]));

        $data = $response->getData(true);

        $this->assertSame('2026-10-11', $data['date']);
        $this->assertSame('2569-10-11', $data['date_be']);
        $this->assertSame('2569-10-11T08:30:00+07:00', $data['datetime_be']);
        $this->assertSame('2569-10-11 08:30:00', $data['recorded_at_be']);
        $this->assertSame('2570-01-09', $data['items'][0]['due_date_be']);
        $this->assertArrayNotHasKey('label_be', $data['items'][0]);
    }

    public function test_it_preserves_an_existing_buddhist_era_field(): void
    {
        $middleware = new AddBuddhistEraDates();
        $request = Request::create('/api/example', 'POST');

        $response = $middleware->handle($request, fn () => response()->json([
            'date' => '2026-10-11',
            'date_be' => 'กำหนดเอง',
        ]));

        $this->assertSame('กำหนดเอง', $response->getData(true)['date_be']);
    }
}
