<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Message\DestroyMessageRequest;
use App\Http\Requests\Api\Message\IndexMessageRequest;
use App\Http\Requests\Api\Message\ShowMessageRequest;
use App\Http\Requests\Api\Message\StoreMessageRequest;
use App\Http\Requests\Api\Message\UpdateMessageRequest;
use App\Http\Resources\Api\MessageCollection;
use App\Http\Resources\Api\MessageResource;
use App\Models\Message;
use Illuminate\Http\JsonResponse;

class MessageController extends Controller
{
    public function index(IndexMessageRequest $request): JsonResponse
    {
        $paginator = Message::query()
            ->paginate($request->validated('per_page'), ['*'], $request->validated('page'));

        return response()->json(new MessageCollection($paginator));
    }

    public function store(StoreMessageRequest $request): JsonResponse
    {
        $message = Message::query()->create($request->validated());
        
        return response()->json(new MessageResource($message), 201);
    }

    public function update(UpdateMessageRequest $request, Message $message): JsonResponse
    {
        $message->update($request->validated());
        
        return response()->json(new MessageResource($message));
    }

    public function show(ShowMessageRequest $request, Message $message): JsonResponse
    {
        return response()->json(new MessageResource($message));
    }

    public function destroy(DestroyMessageRequest $request, Message $message): JsonResponse
    {
        $message->delete();

        return response()->json(null, 204);
    }
}
