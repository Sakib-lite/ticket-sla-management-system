<?php

namespace App\Support\Imports;

use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;

final readonly class TicketCsvReader
{
    public const REQUIRED_COLUMNS = ['subject', 'description', 'priority', 'category', 'customer_email'];

    public const OPTIONAL_COLUMNS = ['external_reference', 'created_at'];

    public function __construct(private string $path) {}

    /** @return LazyCollection<int, array{row: int, data: array<string, string>}> */
    public function rows(): LazyCollection
    {
        return LazyCollection::make(function () {
            $handle = $this->open();

            try {
                $header = $this->readHeader($handle);
                $line = 1;

                while (($values = $this->readLine($handle)) !== false) {
                    $line++;

                    if ($this->isBlank($values)) {
                        continue;
                    }

                    yield ['row' => $line, 'data' => $this->combine($header, $values)];
                }
            } finally {
                fclose($handle);
            }
        });
    }

    public function countRows(): int
    {
        $handle = $this->open();
        $count = 0;

        try {
            $this->readHeader($handle);

            while (($values = $this->readLine($handle)) !== false) {
                if (! $this->isBlank($values)) {
                    $count++;
                }
            }
        } finally {
            fclose($handle);
        }

        return $count;
    }

    /** @return resource */
    private function open()
    {
        $handle = fopen($this->path, 'r');

        if ($handle === false) {
            throw new InvalidCsvException('The import file could not be opened.');
        }

        return $handle;
    }

    /**
     * @param  resource  $handle
     * @return list<string|null>|false
     */
    private function readLine($handle): array|false
    {
        return fgetcsv($handle, null, ',', '"', '');
    }

    /**
     * @param  resource  $handle
     * @return list<string>
     */
    private function readHeader($handle): array
    {
        $header = $this->readLine($handle);

        if ($header === false || $this->isBlank($header)) {
            throw new InvalidCsvException('The file is empty.');
        }

        $columns = array_map(fn (?string $column) => Str::snake(strtolower(trim((string) $column))), $header);
        $missing = array_diff(self::REQUIRED_COLUMNS, $columns);

        if ($missing !== []) {
            throw new InvalidCsvException('Missing required columns: '.implode(', ', $missing).'.');
        }

        return $columns;
    }

    /** @param  list<string|null>  $values */
    private function isBlank(array $values): bool
    {
        return $values === [null] || trim(implode('', array_map(strval(...), $values))) === '';
    }

    /**
     * @param  list<string>  $header
     * @param  list<string|null>  $values
     * @return array<string, string>
     */
    private function combine(array $header, array $values): array
    {
        $values = array_slice(array_pad($values, count($header), ''), 0, count($header));

        return array_map(fn (?string $value) => trim((string) $value), array_combine($header, $values));
    }
}
