<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Inertia\Inertia;
use Inertia\Response;

abstract class MasterDataController extends Controller implements HasMiddleware
{
    /**
     * @return class-string<Model>
     */
    abstract protected function modelClass(): string;

    /**
     * Permission prefix, e.g. "categories" for "categories.view".
     */
    abstract protected function permissionPrefix(): string;

    /**
     * Inertia page directory, e.g. "master-data/categories".
     */
    abstract protected function pagePath(): string;

    /**
     * Route name prefix, e.g. "master-data.categories".
     */
    abstract protected function routePrefix(): string;

    /**
     * Human readable singular label used in flash messages.
     */
    abstract protected function label(): string;

    /**
     * Columns accepted on create and update.
     *
     * @return array<string, array<mixed>>
     */
    abstract protected function rules(Request $request, ?Model $record): array;

    /**
     * @return array<string, mixed>
     */
    abstract protected function row(Model $record): array;

    /**
     * Relations eager loaded when listing and editing records.
     *
     * @return array<int, string>
     */
    protected function relations(): array
    {
        return [];
    }

    /**
     * Extra props (e.g. select options) passed to the form page.
     *
     * @return array<string, mixed>
     */
    protected function formOptions(Request $request): array
    {
        return [];
    }

    /**
     * Get the middleware that should be assigned to the controller.
     *
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        $prefix = (new static)->permissionPrefix();

        return [
            new Middleware("permission:{$prefix}.view", only: ['index']),
            new Middleware("permission:{$prefix}.create", only: ['create', 'store']),
            new Middleware("permission:{$prefix}.update", only: ['edit', 'update']),
            new Middleware("permission:{$prefix}.delete", only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        return Inertia::render($this->pagePath().'/index', [
            'rows' => $this->outletRecords($request)
                ->with($this->relations())
                ->orderBy('name')
                ->get()
                ->map(fn (Model $record) => $this->row($record)),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render($this->pagePath().'/form', [
            'record' => null,
            ...$this->formOptions($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->modelClass()::create([
            ...$request->validate($this->rules($request, null)),
            'team_id' => $this->outlet($request)->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':label created.', ['label' => $this->label()])]);

        return to_route($this->routePrefix().'.index');
    }

    public function edit(Request $request, string $currentTeam, int $id): Response
    {
        return Inertia::render($this->pagePath().'/form', [
            'record' => $this->row($this->outletRecords($request)->with($this->relations())->findOrFail($id)),
            ...$this->formOptions($request),
        ]);
    }

    public function update(Request $request, string $currentTeam, int $id): RedirectResponse
    {
        $record = $this->outletRecords($request)->findOrFail($id);
        $record->update($request->validate($this->rules($request, $record)));

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':label updated.', ['label' => $this->label()])]);

        return to_route($this->routePrefix().'.index');
    }

    public function destroy(Request $request, string $currentTeam, int $id): RedirectResponse
    {
        $this->outletRecords($request)->findOrFail($id)->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':label deleted.', ['label' => $this->label()])]);

        return to_route($this->routePrefix().'.index');
    }

    /**
     * The outlet the request is operating on.
     */
    protected function outlet(Request $request): Team
    {
        return $request->user()->currentTeam;
    }

    /**
     * Records belonging to the current outlet only.
     *
     * @return Builder<Model>
     */
    protected function outletRecords(Request $request): Builder
    {
        return $this->modelClass()::query()->where('team_id', $this->outlet($request)->id);
    }

    /**
     * Unique rule on the "name" column within the current outlet, ignoring the record being updated.
     */
    protected function uniqueName(Request $request, ?Model $record): Unique
    {
        return Rule::unique((new ($this->modelClass()))->getTable(), 'name')
            ->where('team_id', $this->outlet($request)->id)
            ->ignore($record?->getKey());
    }
}
