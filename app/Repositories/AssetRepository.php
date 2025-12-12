<?php
namespace App\Repositories;

use App\Models\Asset;
use App\Contracts\AssetRepositoryInterface;

class AssetRepository implements AssetRepositoryInterface
{
    public function findByUserSymbolForUpdate(int $userId, string $symbol): ?Asset
    {
        return Asset::where('user_id', $userId)->where('symbol', $symbol)->lockForUpdate()->first();
    }

    public function firstOrCreate(int $userId, string $symbol): Asset
    {
        return Asset::firstOrCreate([
            'user_id' => $userId,
            'symbol' => $symbol
        ]);
    }

    public function update(Asset $asset, array $data): Asset
    {
        $asset->fill($data);
        $asset->save();
        return $asset;
    }
}
