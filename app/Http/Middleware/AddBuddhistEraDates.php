<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddBuddhistEraDates
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!$response instanceof JsonResponse) {
            return $response;
        }

        $response->setData($this->appendBuddhistEraDates($response->getData(true)));

        return $response;
    }

    private function appendBuddhistEraDates(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $result[$key] = is_array($value)
                ? $this->appendBuddhistEraDates($value)
                : $value;

            if (!is_string($key) || !$this->isDateKey($key)) {
                continue;
            }

            $buddhistEraValue = $this->toBuddhistEra($value);
            $buddhistEraKey = $key . '_be';

            if ($buddhistEraValue !== null && !array_key_exists($buddhistEraKey, $data)) {
                $result[$buddhistEraKey] = $buddhistEraValue;
            }
        }

        return $result;
    }

    private function isDateKey(string $key): bool
    {
        return $key === 'date'
            || $key === 'datetime'
            || str_ends_with($key, '_date')
            || str_ends_with($key, '_datetime')
            || str_ends_with($key, '_at');
    }

    private function toBuddhistEra(mixed $value): ?string
    {
        if (!is_string($value)
            || !preg_match('/^(\d{4})(-\d{2}-\d{2}(?:[ T].*)?)$/', $value, $matches)) {
            return null;
        }

        return ((int) $matches[1] + 543) . $matches[2];
    }
}
