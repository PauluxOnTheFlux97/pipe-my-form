<?php
declare(strict_types=1);

namespace PaulMaudry\PipeMyForm\Flows;

final class FlowEngine
{
    /** @return array<string,mixed>|null */
    public function next(array $flow, string $currentId, mixed $answer): ?array
    {
        $nodes = [];
        foreach ($flow['nodes'] ?? [] as $node) {
            if (is_array($node) && isset($node['id'])) {
                $nodes[$node['id']] = $node;
            }
        }
        if (!isset($nodes[$currentId])) {
            throw new \InvalidArgumentException('Unknown current node');
        }
        $current = $nodes[$currentId];
        $this->assertAnswer($current, $answer);
        $fallback = null;
        foreach (($current['edges'] ?? []) as $edge) {
            if (!isset($edge['when'])) {
                $fallback ??= $edge['to'];
                continue;
            }
            $condition = $edge['when'];
            $match = match ($condition['operator']) {
                'equals' => is_string($answer) && $answer === $condition['value'],
                'not_equals' => is_string($answer) && $answer !== $condition['value'],
                'contains' => (is_array($answer) && in_array($condition['value'], $answer, true))
                    || (is_string($answer) && str_contains($answer, $condition['value'])),
                default => false,
            };
            if ($match) {
                return $nodes[$edge['to']] ?? null;
            }
        }
        return $fallback === null ? null : ($nodes[$fallback] ?? null);
    }

    private function assertAnswer(array $node, mixed $answer): void
    {
        $type = $node['type'];
        if ($type === 'info') {
            if ($answer !== null) {
                throw new \InvalidArgumentException('Info nodes accept null only');
            }
            return;
        }
        if ($type === 'consent') {
            if (!is_bool($answer) || (!empty($node['required']) && $answer !== true)) {
                throw new \InvalidArgumentException('Invalid consent');
            }
            return;
        }
        if ($type === 'multiple_choice') {
            if (!is_array($answer) || !array_is_list($answer) || count(array_unique($answer)) !== count($answer)) {
                throw new \InvalidArgumentException('Invalid multiple choice');
            }
            foreach ($answer as $value) {
                if (!is_string($value) || !in_array($value, $node['options'], true)) {
                    throw new \InvalidArgumentException('Unknown option');
                }
            }
            if (!empty($node['required']) && !$answer) {
                throw new \InvalidArgumentException('Answer required');
            }
            return;
        }
        if (!is_string($answer) || strlen($answer) > 4096) {
            throw new \InvalidArgumentException('Answer must be a string up to 4096 bytes');
        }
        if (!empty($node['required']) && trim($answer) === '') {
            throw new \InvalidArgumentException('Answer required');
        }
        if ($type === 'single_choice' && !in_array($answer, $node['options'], true)) {
            throw new \InvalidArgumentException('Unknown option');
        }
        if ($type === 'email' && $answer !== '' && !filter_var($answer, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email');
        }
    }
}
