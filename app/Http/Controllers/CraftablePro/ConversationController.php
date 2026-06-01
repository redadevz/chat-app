<?php

namespace App\Http\Controllers\CraftablePro;

use App\Exports\CraftablePro\ConversationExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\CraftablePro\Conversation\BulkDestroyConversationRequest;
use App\Http\Requests\CraftablePro\Conversation\CreateConversationRequest;
use App\Http\Requests\CraftablePro\Conversation\DestroyConversationRequest;
use App\Http\Requests\CraftablePro\Conversation\EditConversationRequest;
use App\Http\Requests\CraftablePro\Conversation\ExportConversationRequest;
use App\Http\Requests\CraftablePro\Conversation\IndexConversationRequest;
use App\Http\Requests\CraftablePro\Conversation\StoreConversationRequest;
use App\Http\Requests\CraftablePro\Conversation\UpdateConversationRequest;
use App\Models\Conversation;
use Brackets\CraftablePro\Queries\Filters\FuzzyFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ConversationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(IndexConversationRequest $request): Response | JsonResponse | RedirectResponse
    {
        $defaultSort = "id";

        if (!$request->has('sort')) {
            return redirect()->route($request->route()->getName(), ['sort' => $defaultSort]);
        }

        $conversationsQuery = QueryBuilder::for(Conversation::class)
            ->allowedFilters([
                AllowedFilter::custom('search', new FuzzyFilter(
                    'id', 'created_by', 'name', 'type'
                )),
            ])
            ->defaultSort($defaultSort)
            ->allowedSorts(['id', 'created_by', 'name', 'type', 'created_at']);

        if ($request->wantsJson() && $request->get('bulk_select_all')) {
            return response()->json($conversationsQuery->select(['id'])->pluck('id'));
        }

        $conversations = $conversationsQuery
            ->with([])
            ->select('id', 'created_by', 'name', 'type', 'created_at')
            ->paginate($request->get('per_page'))->withQueryString();

        Session::put('conversations_url', $request->fullUrl());

        return Inertia::render('Conversation/Index', [
            'conversations' => $conversations,
        ]);
    }

     /**
     * Show the form for creating a new resource.
     */
    public function create(CreateConversationRequest $request): Response
    {
        return Inertia::render('Conversation/Create', [
            
        ]);
    }

    /**
    * Store a newly created resource in storage.
    */
    public function store(StoreConversationRequest $request): RedirectResponse
    {
        $conversation = Conversation::create($request->validated());
        
        return redirect()->route('craftable-pro.conversations.index')->with(['message' => ___('craftable-pro', 'Operation successful')]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EditConversationRequest $request, Conversation $conversation): Response
    {
        
        return Inertia::render('Conversation/Edit', [
            'conversation' => $conversation,
            
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateConversationRequest $request, Conversation $conversation): RedirectResponse
    {
        $conversation->update($request->validated());
        
        if (session('conversations_url')) {
            return redirect(session('conversations_url'))->with(['message' => ___('craftable-pro', 'Operation successful')]);
        }

        return redirect()->route('craftable-pro.conversations.index')->with(['message' => ___('craftable-pro', 'Operation successful')]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DestroyConversationRequest $request, Conversation $conversation): RedirectResponse
    {
        
        $conversation->delete();

        return redirect()->back()->with(['message' => ___('craftable-pro', 'Operation successful')]);
    }

    /**
     * Bulk destroy resource.
     */
    public function bulkDestroy(BulkDestroyConversationRequest $request): RedirectResponse
    {
        // Mass delete of resource
        DB::transaction(function () use ($request) {
            collect($request->validated()['ids'])
                ->chunk(1000)
                ->each(function ($bulkChunk) {
                    Conversation::whereIn('id', $bulkChunk)->delete();
                });
        });

        // Individual delete of resource items
        //        DB::transaction(function () use ($request) {
        //            collect($request->validated()['ids'])->each(function ($id) {
        //                Conversation::find($id)->delete();
        //            });
        //        });

        return redirect()->back()->with(['message' => ___('craftable-pro', 'Operation successful')]);
    }

    /**
     * Export
     */
    public function export(ExportConversationRequest $request): BinaryFileResponse
    {
        return Excel::download(new ConversationExport($request->all()), 'conversations-'.now()->format("dmYHi").'.xlsx');
    }
}
