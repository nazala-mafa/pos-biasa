<?php

namespace App\Repositories;

use App\Models\Product;
use App\Models\Team;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * @extends OutletScopedRepository<Product>
 */
class ProductRepository extends OutletScopedRepository
{
    protected function modelClass(): string
    {
        return Product::class;
    }

    protected function relations(): array
    {
        return ['productCategory:id,name', 'unit:id,name,abbreviation', 'tax:id,name,rate'];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Team $outlet, array $data): Model
    {
        return parent::create($outlet, $this->withStoredPhoto($data));
    }

    /**
     * @param  Product  $record
     * @param  array<string, mixed>  $data
     */
    public function update(Model $record, array $data): Model
    {
        $replacesPhoto = ($data['photo'] ?? null) instanceof UploadedFile || ! empty($data['remove_photo']);
        $previousPhoto = $record->photo_path;

        parent::update($record, $this->withStoredPhoto($data, removeCurrent: ! empty($data['remove_photo'])));

        if ($replacesPhoto && $previousPhoto) {
            Storage::disk('public')->delete($previousPhoto);
        }

        return $record;
    }

    /**
     * @param  Product  $record
     */
    public function delete(Model $record): void
    {
        $photo = $record->photo_path;

        parent::delete($record);

        if ($photo) {
            Storage::disk('public')->delete($photo);
        }
    }

    /**
     * Replace the uploaded "photo" file in the data with its stored "photo_path".
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function withStoredPhoto(array $data, bool $removeCurrent = false): array
    {
        $photo = $data['photo'] ?? null;

        unset($data['photo'], $data['remove_photo']);

        if ($photo instanceof UploadedFile) {
            $data['photo_path'] = $photo->store('products', 'public');
        } elseif ($removeCurrent) {
            $data['photo_path'] = null;
        }

        return $data;
    }

    /**
     * Products of the outlet with their current stock level.
     *
     * @return Collection<int, Product>
     */
    public function withStock(Team $outlet, bool $activeOnly = false): Collection
    {
        return $outlet->products()
            ->when($activeOnly, fn ($query) => $query->where('is_active', true))
            ->with('unit:id,abbreviation')
            ->withSum('stockMovements as stock', 'quantity')
            ->orderBy('name')
            ->get();
    }
}
