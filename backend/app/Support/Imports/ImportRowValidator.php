<?php

namespace App\Support\Imports;

use App\Enums\ImportErrorKind;
use App\Enums\Role;
use App\Enums\TicketPriority;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class ImportRowValidator
{
    /** @var array<string, int>|null */
    private ?array $categories = null;

    /**
     * @param  list<array{row: int, data: array<string, string>}>  $rows
     * @param  array<string, true>  $seenReferences
     */
    public function validate(array $rows, array &$seenReferences): ImportChunkOutcome
    {
        $customers = $this->customersByEmail($rows);
        $existingReferences = $this->existingReferences($rows);
        $valid = [];
        $rejected = [];

        foreach ($rows as $row) {
            $data = $this->normalize($row['data']);
            $reference = $data['external_reference'];

            if ($reference !== '' && (isset($seenReferences[$reference]) || isset($existingReferences[$reference]))) {
                $rejected[] = $this->reject($row, ImportErrorKind::Duplicate, ["A ticket with external reference \"{$reference}\" already exists."]);

                continue;
            }

            $validator = Validator::make($data, $this->rules($customers));

            if ($validator->fails()) {
                $rejected[] = $this->reject($row, ImportErrorKind::Invalid, $validator->errors()->all());

                continue;
            }

            if ($reference !== '') {
                $seenReferences[$reference] = true;
            }

            $valid[] = [
                'row' => $row['row'],
                'external_reference' => $reference !== '' ? $reference : null,
                'subject' => $data['subject'],
                'description' => $data['description'],
                'priority' => TicketPriority::from($data['priority']),
                'category_id' => $this->categories()[$data['category']],
                'created_by' => $customers[$data['customer_email']],
                'created_at' => $data['created_at'] !== '' ? $data['created_at'] : null,
            ];
        }

        return new ImportChunkOutcome($valid, $rejected);
    }

    /**
     * @param  array<string, string>  $data
     * @return array<string, string>
     */
    private function normalize(array $data): array
    {
        $data += array_fill_keys([...TicketCsvReader::REQUIRED_COLUMNS, ...TicketCsvReader::OPTIONAL_COLUMNS], '');

        return [
            ...$data,
            'priority' => strtolower($data['priority']),
            'category' => strtolower($data['category']),
            'customer_email' => strtolower($data['customer_email']),
        ];
    }

    /**
     * @param  array<string, int>  $customers
     * @return array<string, list<mixed>>
     */
    private function rules(array $customers): array
    {
        return [
            'external_reference' => ['nullable', 'string', 'max:100'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'priority' => ['required', Rule::in(array_map(fn (TicketPriority $priority) => $priority->value, TicketPriority::cases()))],
            'category' => ['required', Rule::in(array_keys($this->categories()))],
            'customer_email' => ['required', 'email', Rule::in(array_keys($customers))],
            'created_at' => ['nullable', 'date'],
        ];
    }

    /** @return array<string, int> */
    private function categories(): array
    {
        return $this->categories ??= Category::query()
            ->active()
            ->pluck('id', 'name')
            ->mapWithKeys(fn (int $id, string $name) => [strtolower($name) => $id])
            ->all();
    }

    /**
     * @param  list<array{row: int, data: array<string, string>}>  $rows
     * @return array<string, int>
     */
    private function customersByEmail(array $rows): array
    {
        $emails = collect($rows)
            ->map(fn (array $row) => strtolower($row['data']['customer_email'] ?? ''))
            ->filter()
            ->unique()
            ->values();

        return User::query()
            ->where('role', Role::Customer)
            ->whereIn('email', $emails)
            ->pluck('id', 'email')
            ->mapWithKeys(fn (int $id, string $email) => [strtolower($email) => $id])
            ->all();
    }

    /**
     * @param  list<array{row: int, data: array<string, string>}>  $rows
     * @return array<string, true>
     */
    private function existingReferences(array $rows): array
    {
        $references = collect($rows)
            ->map(fn (array $row) => $row['data']['external_reference'] ?? '')
            ->filter()
            ->unique()
            ->values();

        return Ticket::query()
            ->whereIn('external_reference', $references)
            ->pluck('external_reference')
            ->mapWithKeys(fn (string $reference) => [$reference => true])
            ->all();
    }

    /**
     * @param  array{row: int, data: array<string, string>}  $row
     * @param  list<string>  $messages
     * @return array{row: int, kind: string, messages: list<string>, raw: array<string, string>}
     */
    private function reject(array $row, ImportErrorKind $kind, array $messages): array
    {
        return ['row' => $row['row'], 'kind' => $kind->value, 'messages' => $messages, 'raw' => $row['data']];
    }
}
