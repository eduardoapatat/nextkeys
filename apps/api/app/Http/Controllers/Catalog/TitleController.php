<?php

namespace App\Http\Controllers\Catalog;

use App\Actions\Catalog\CreateTitle;
use App\Actions\Catalog\DeleteTitle;
use App\Actions\Catalog\UpdateTitle;
use App\DataTables\TitlesTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\TitleRequest;
use App\Models\Title;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TitleController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('catalog/titles/Index', [
            'titles' => fn () => TitlesTable::make(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('catalog/titles/Create');
    }

    public function store(TitleRequest $request, CreateTitle $createTitle): RedirectResponse
    {
        $createTitle->handle($request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Título creado.']);

        return to_route('titles.index');
    }

    public function edit(Title $title): Response
    {
        return Inertia::render('catalog/titles/Edit', [
            'title' => $title->only(['id', 'name', 'description']),
        ]);
    }

    public function update(TitleRequest $request, Title $title, UpdateTitle $updateTitle): RedirectResponse
    {
        $updateTitle->handle($title, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Título actualizado.']);

        return to_route('titles.index');
    }

    public function destroy(Title $title, DeleteTitle $deleteTitle): RedirectResponse
    {
        $deleteTitle->handle($title);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Título eliminado.']);

        return to_route('titles.index');
    }
}
