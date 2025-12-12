<?php
namespace App\Contracts;

use App\Models\Asset;

interface AssetRepositoryInterface
{
    public function findByUserSymbolForUpdate(int $userId, string $symbol): ?Asset;

    public function firstOrCreate(int $userId, string $symbol): Asset;

    public function update(Asset $asset, array $data): Asset;
}
