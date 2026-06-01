<?php

namespace App\Exports\CraftablePro;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Brackets\CraftablePro\Queries\Filters\FuzzyFilter;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use App\Models\Conversation;

class ConversationExport implements FromCollection, WithHeadings
{
    protected mixed $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection(): Collection
    {
        return QueryBuilder::for(Conversation::class)
            ->allowedFilters([
                AllowedFilter::custom('search', new FuzzyFilter(
                    'id', 'created_by', 'name', 'type', 'created_at'
                )),
            ])
            ->defaultSort('id')
            ->allowedSorts('id', 'created_by', 'name', 'type', 'created_at')
            ->select(['id', 'created_by', 'name', 'type', 'created_at'])
            ->get();
    }

    public function headings(): array
    {
        return [
            ___('craftable-pro', 'Id'),
            ___('craftable-pro', 'Created By'),
            ___('craftable-pro', 'Name'),
            ___('craftable-pro', 'Type'),
            ___('craftable-pro', 'Created At')
        ];
    }
}
