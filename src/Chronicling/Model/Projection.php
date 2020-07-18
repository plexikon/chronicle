<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Chronicling\Model;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Plexikon\Chronicle\Support\Contract\Chronicling\Model\ProjectionModel;
use Plexikon\Chronicle\Support\Contract\Chronicling\Model\ProjectionProvider;

class Projection extends Model implements ProjectionModel, ProjectionProvider
{
    public $table = self::TABLE;
    public $timestamps = false;
    protected $fillable = ['name', 'position', 'state', 'locked_until'];
    protected $primaryKey = 'no';

    public function newProjection(string $name, string $status): bool
    {
        $projection = $this->newInstance();

        $projection['name'] = $name;
        $projection['status'] = $status;
        $projection['position'] = '{}';
        $projection['state'] = '{}';
        $projection['locked_until'] = null;

        return $projection->save();
    }

    public function findByName(string $name): ?ProjectionModel
    {
        /** @var ProjectionModel $projection */
        $projection = $this->newInstance()->newQuery()
            ->where('name', $name)
            ->first();

        return $projection;
    }

    public function findByNames(string ...$names): array
    {
        return $this->newInstance()->newQuery()
            ->whereIn('name', $names)
            ->orderBy('name')
            ->pluck('name')
            ->toArray();
    }

    public function acquireLock(string $name, string $status, string $lockedUntil, string $now): int
    {
        return $this->newInstance()->newQuery()
            ->where('name', $name)
            ->where(static function (Builder $query) use ($now) {
                $query->whereRaw('locked_until IS NULL OR locked_until < ?', [$now]);
            })->update([
                'status' => $status,
                'locked_until' => $lockedUntil
            ]);
    }

    public function updateStatus(string $name, array $data): int
    {
        return $this->newInstance()->newQuery()
            ->where('name', $name)
            ->update($data);
    }

    public function deleteByName(string $name): int
    {
        return (int)$this->newInstance()->newQuery()
            ->where('name', $name)
            ->delete();
    }

    public function projectionExists(string $name): bool
    {
        try {
            return $this->newInstance()->newQuery()
                ->where('name', $name)
                ->exists();
        } catch (QueryException $queryException) {
            return false;
        }
    }

    public function name(): string
    {
        return $this['name'];
    }

    public function position(): string
    {
        return $this['position'];
    }

    public function state(): string
    {
        return $this['state'];
    }

    public function status(): string
    {
        return $this['status'];
    }

    public function lockedUntil(): ?string
    {
        return $this['locked_until'];
    }
}
