<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Chronicling\Model;

use Illuminate\Database\Eloquent\Model;
use Plexikon\Chronicle\Stream\StreamName;
use Plexikon\Chronicle\Support\Contract\Chronicling\Model\EventStreamModel;
use Plexikon\Chronicle\Support\Contract\Chronicling\Model\EventStreamProvider;

class EventStream extends Model implements EventStreamModel, EventStreamProvider
{
    public $timestamps = false;
    protected $table = self::TABLE;
    protected $fillable = ['stream_name', 'real_stream_name'];

    public function createStream(StreamName $streamName, string $tableName): bool
    {
        return $this->newInstance([
            'real_stream_name' => $streamName->toString(),
            'stream_name' => $tableName
        ])->save();
    }

    public function deleteStream(StreamName $streamName): bool
    {
        $result = $this->newInstance()->newQuery()
            ->where('real_stream_name', $streamName->toString())
            ->delete();

        return 0 !== $result;
    }

    public function filterByStream(array $streamNames): array
    {
        return $this->newInstance()->newQuery()
            ->whereIn('real_stream_name', $streamNames)
            ->get()->transform(fn(string $streamName): StreamName => new StreamName($streamName))
            ->toArray();
    }

    public function allStreamWithoutInternal(): array
    {
        return $this->newInstance()->newQuery()
            ->whereRaw("real_stream_name NOT LIKE '$%'")
            ->pluck('real_stream_name')
            ->toArray();
    }

    public function hasRealStreamName(StreamName $streamName): bool
    {
        return $this->newInstance()->newQuery()
            ->where('real_stream_name', $streamName->toString())
            ->exists();
    }

    public function realStreamName(): string
    {
        return $this['real_stream_name'];
    }

    public function tableName(): string
    {
        return $this['stream_name'];
    }
}
