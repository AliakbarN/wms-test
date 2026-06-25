<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CategoryService
{
    public function create(array $data): Category
    {
        return DB::transaction(function () use ($data): Category {
            $this->validateParent(null, $data['provider_id'], $data['parent_id'] ?? null);

            return Category::query()->create($data);
        });
    }

    public function update(Category $category, array $data): Category
    {
        return DB::transaction(function () use ($category, $data): Category {
            $lockedCategory = Category::query()->lockForUpdate()->findOrFail($category->getKey());
            $providerId = $data['provider_id'] ?? $lockedCategory->provider_id;
            $parentId = array_key_exists('parent_id', $data) ? $data['parent_id'] : $lockedCategory->parent_id;

            if (
                $providerId !== $lockedCategory->provider_id
                && ($lockedCategory->children()->exists() || $lockedCategory->products()->exists())
            ) {
                throw ValidationException::withMessages([
                    'provider_id' => ['A category with children or products cannot change provider.'],
                ]);
            }

            $this->validateParent($lockedCategory, $providerId, $parentId);
            $lockedCategory->update($data);

            return $lockedCategory->refresh();
        });
    }

    private function validateParent(?Category $category, int $providerId, ?int $parentId): void
    {
        $visited = [];

        while ($parentId !== null) {
            if (isset($visited[$parentId])) {
                throw ValidationException::withMessages([
                    'parent_id' => ['The category hierarchy contains a circular relationship.'],
                ]);
            }

            $visited[$parentId] = true;
            $parent = Category::query()->lockForUpdate()->findOrFail($parentId);

            if ($parent->provider_id !== $providerId) {
                throw ValidationException::withMessages([
                    'parent_id' => ['The parent category must belong to the same provider.'],
                ]);
            }

            if ($category !== null && $parent->getKey() === $category->getKey()) {
                throw ValidationException::withMessages([
                    'parent_id' => ['A category cannot be moved under itself or one of its descendants.'],
                ]);
            }

            $parentId = $parent->parent_id;
        }
    }
}
