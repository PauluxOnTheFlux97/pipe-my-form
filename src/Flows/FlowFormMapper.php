<?php
declare(strict_types=1);

namespace PaulMaudry\PipeMyForm\Flows;

/**
 * Normalizes the structured editor's submitted form into flow schema v1.
 * This is deliberately independent of WordPress to allow fast unit testing.
 */
final class FlowFormMapper
{
    private const TYPES = ['text', 'textarea', 'email', 'tel', 'single_choice', 'multiple_choice', 'consent', 'info'];
    private const OPERATORS = ['equals', 'not_equals', 'contains'];

    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public function fromInput(array $input): array
    {
        $rows = $input['nodes'] ?? null;
        if (!is_array($rows) || count($rows) < 1 || count($rows) > 100) {
            throw new \InvalidArgumentException('A flow requires between 1 and 100 questions.');
        }
        $nodes = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                throw new \InvalidArgumentException('Invalid question.');
            }
            $id = $this->string($row['id'] ?? '', 64);
            if (!preg_match('/^[a-z][a-z0-9_-]{0,63}$/D', $id)) {
                throw new \InvalidArgumentException('Question IDs must start with a lowercase letter and contain only lowercase letters, numbers, underscores or hyphens.');
            }
            $type = $this->string($row['type'] ?? '', 32);
            if (!in_array($type, self::TYPES, true)) {
                throw new \InvalidArgumentException('Unknown question type.');
            }
            $node = [
                'id' => $id,
                'type' => $type,
                'label' => $this->string($row['label'] ?? '', 1000),
                'required' => ($row['required'] ?? '') === '1',
                'edges' => [],
            ];
            if (in_array($type, ['single_choice', 'multiple_choice'], true)) {
                $rawOptions = $this->string($row['options'] ?? '', 8000);
                $options = array_values(array_filter(
                    array_map('trim', preg_split('/\r\n|\r|\n/', $rawOptions) ?: []),
                    static fn(string $item): bool => $item !== ''
                ));
                if (count($options) > 50) {
                    throw new \InvalidArgumentException('Too many answer options (max 50).');
                }
                $node['options'] = $options;
            }
            $edges = $row['edges'] ?? [];
            if (!is_array($edges) || count($edges) > 50) {
                throw new \InvalidArgumentException('Too many connections (max 50 per question).');
            }
            foreach ($edges as $edge) {
                if (!is_array($edge)) {
                    throw new \InvalidArgumentException('Invalid connection.');
                }
                $to = $this->string($edge['to'] ?? '', 64);
                if ($to === '') {
                    continue;
                }
                $operator = $this->string($edge['operator'] ?? '', 32);
                $normalized = ['to' => $to];
                if ($operator !== '') {
                    if (!in_array($operator, self::OPERATORS, true)) {
                        throw new \InvalidArgumentException('Unknown connection condition.');
                    }
                    $normalized['when'] = [
                        'operator' => $operator,
                        'value' => $this->string($edge['value'] ?? '', 1000),
                    ];
                }
                $node['edges'][] = $normalized;
            }
            $nodes[] = $node;
        }

        return [
            'schema_version' => 1,
            'start' => $this->string($input['start'] ?? '', 64),
            'nodes' => $nodes,
        ];
    }

    private function string(mixed $value, int $maxLength): string
    {
        if (!is_string($value) || strlen($value) > $maxLength) {
            throw new \InvalidArgumentException('Invalid or oversized input field.');
        }
        return $value;
    }
}
