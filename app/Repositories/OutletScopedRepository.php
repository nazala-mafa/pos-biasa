<?php

namespace App\Repositories;

use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 */
abstract class OutletScopedRepository
{
    /**
     * @return class-string<TModel>
     */
    abstract protected function modelClass(): string;

    /**
     * Relations eager loaded when listing and finding records.
     *
     * @return array<int, string>
     */
    protected function relations(): array
    {
        return [];
    }

    /**
     * @return Collection<int, TModel>
     */
    public function all(Team $outlet): Collection
    {
        return $this->query($outlet)->with($this->relations())->orderBy('name')->get();
    }

    /**
     * @return TModel
     */
    public function findOrFail(Team $outlet, int $id): Model
    {
        return $this->query($outlet)->with($this->relations())->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return TModel
     */
    public function create(Team $outlet, array $data): Model
    {
        return $this->modelClass()::query()->create([...$data, 'team_id' => $outlet->id]);
    }

    /**
     * @param  TModel  $record
     * @param  array<string, mixed>  $data
     * @return TModel
     */
    public function update(Model $record, array $data): Model
    {
        $record->update($data);

        return $record;
    }

    /**
     * @param  TModel  $record
     */
    public function delete(Model $record): void
    {
        $record->delete();
    }

    /**
     * Records belonging to the given outlet only.
     *
     * @return Builder<TModel>
     */
    protected function query(Team $outlet): Builder
    {
        return $this->modelClass()::query()->where('team_id', $outlet->id);
    }
}
