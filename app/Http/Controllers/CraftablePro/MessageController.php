<?php

namespace App\Http\Controllers\CraftablePro;

use App\Http\Controllers\Controller;
use App\Http\Requests\CraftablePro\Message\BulkDestroyMessageRequest;
use App\Http\Requests\CraftablePro\Message\CreateMessageRequest;
use App\Http\Requests\CraftablePro\Message\DestroyMessageRequest;
use App\Http\Requests\CraftablePro\Message\EditMessageRequest;
use App\Http\Requests\CraftablePro\Message\IndexMessageRequest;
use App\Http\Requests\CraftablePro\Message\StoreMessageRequest;
use App\Http\Requests\CraftablePro\Message\UpdateMessageRequest;
use App\Models\Message;
use Brackets\CraftablePro\Queries\Filters\FuzzyFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class MessageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(IndexMessageRequest $request): Response | JsonResponse | RedirectResponse
    {
        $defaultSort = "id";

        if (!$request->has('sort')) {
            return redirect()->route($request->route()->getName(), ['sort' => $defaultSort]);
        }

        $messagesQuery = QueryBuilder::for(Message::class)
            ->allowedFilters([
                AllowedFilter::custom('search', new FuzzyFilter(
                    'id', 'conversation_id', 'user_id', 'reply_to_id', 'body', 'type'
                )),
            ])
            ->defaultSort($defaultSort)
            ->allowedSorts(['id', 'conversation_id', 'user_id', 'reply_to_id', 'body', 'type', 'created_at']);

        if ($request->wantsJson() && $request->get('bulk_select_all')) {
            return response()->json($messagesQuery->select(['id'])->pluck('id'));
        }

        $messages = $messagesQuery
            ->with([])
            ->select('id', 'conversation_id', 'user_id', 'reply_to_id', 'body', 'type', 'created_at')
            ->paginate($request->get('per_page'))->withQueryString();

        Session::put('messages_url', $request->fullUrl());

        return Inertia::render('Message/Index', [
            'messages' => $messages,
        ]);
    }

     /**
     * Show the form for creating a new resource.
     */
    public function create(CreateMessageRequest $request): Response
    {
        return Inertia::render('Message/Create', [
            
        ]);
    }

    /**
    * Store a newly created resource in storage.
    */
    public function store(StoreMessageRequest $request): RedirectResponse
    {
        $message = Message::create($request->validated());
        
        return redirect()->route('craftable-pro.messages.index')->with(['message' => ___('craftable-pro', 'Operation successful')]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EditMessageRequest $request, Message $message): Response
    {
        
        return Inertia::render('Message/Edit', [
            'message' => $message,
            
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMessageRequest $request, Message $message): RedirectResponse
    {
        $message->update($request->validated());
        
        if (session('messages_url')) {
            return redirect(session('messages_url'))->with(['message' => ___('craftable-pro', 'Operation successful')]);
        }

        return redirect()->route('craftable-pro.messages.index')->with(['message' => ___('craftable-pro', 'Operation successful')]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DestroyMessageRequest $request, Message $message): RedirectResponse
    {
        
        $message->delete();

        return redirect()->back()->with(['message' => ___('craftable-pro', 'Operation successful')]);
    }

    /**
     * Bulk destroy resource.
     */
    public function bulkDestroy(BulkDestroyMessageRequest $request): RedirectResponse
    {
        // Mass delete of resource
        DB::transaction(function () use ($request) {
            collect($request->validated()['ids'])
                ->chunk(1000)
                ->each(function ($bulkChunk) {
                    Message::whereIn('id', $bulkChunk)->delete();
                });
        });

        // Individual delete of resource items
        //        DB::transaction(function () use ($request) {
        //            collect($request->validated()['ids'])->each(function ($id) {
        //                Message::find($id)->delete();
        //            });
        //        });

        return redirect()->back()->with(['message' => ___('craftable-pro', 'Operation successful')]);
    }
}
