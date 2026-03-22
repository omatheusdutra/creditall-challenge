<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;

trait InteractsWithApiResponses
{
    protected function ok(JsonResource|AnonymousResourceCollection $resource, string $message): JsonResponse
    {
        return $resource
            ->additional(['message' => $message])
            ->response();
    }

    protected function created(JsonResource $resource, string $message): JsonResponse
    {
        return $resource
            ->additional(['message' => $message])
            ->response()
            ->setStatusCode(JsonResponse::HTTP_CREATED);
    }

    protected function noContent(): Response
    {
        return response()->noContent();
    }
}
