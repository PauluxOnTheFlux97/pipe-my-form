<?php
declare(strict_types=1);

namespace PaulMaudry\PipeMyForm\Flows;

final class FlowValidator
{
    private const TYPES = ['text', 'textarea', 'email', 'tel', 'single_choice', 'multiple_choice', 'consent', 'info'];

    /** @return list<string> */
    public function validate(array $flow): array
    {
        $errors = [];
        if (($flow['schema_version'] ?? null) !== 1) {
            $errors[] = 'schema_version must be 1';
        }
        $nodes = $flow['nodes'] ?? null;
        $start = $flow['start'] ?? null;
        if (!is_array($nodes) || !array_is_list($nodes) || !$nodes || count($nodes) > 100) {
            return [...$errors, 'nodes must be a nonempty list of at most 100 nodes'];
        }
        if (!is_string($start) || $start === '') {
            $errors[] = 'start must be a nonempty node ID';
        }
        $byId = [];
        foreach ($nodes as $index => $node) {
            if (!is_array($node)) {
                $errors[] = "nodes[$index] must be an object";
                continue;
            }
            $id = $node['id'] ?? null;
            if (!is_string($id) || !preg_match('/^[a-z][a-z0-9_-]{0,63}$/', $id)) {
                $errors[] = "nodes[$index].id is invalid";
                continue;
            }
            if (isset($byId[$id])) {
                $errors[] = "duplicate node: $id";
                continue;
            }
            $byId[$id] = $node;
            if (!in_array($node['type'] ?? null, self::TYPES, true)) {
                $errors[] = "invalid type for $id";
            }
            if (!is_string($node['label'] ?? null) || trim($node['label']) === '') {
                $errors[] = "missing label for $id";
            }
            if (in_array($node['type'] ?? null, ['single_choice', 'multiple_choice'], true)) {
                $options = $node['options'] ?? null;
                if (!is_array($options) || !array_is_list($options) || !$options || count($options) > 50 || count(array_unique($options)) !== count($options)) {
                    $errors[] = "invalid options for $id";
                } else {
                    foreach ($options as $option) {
                        if (!is_string($option) || trim($option) === '') {
                            $errors[] = "invalid option for $id";
                            break;
                        }
                    }
                }
            }
            if (isset($node['required']) && !is_bool($node['required'])) {
                $errors[] = "required must be boolean for $id";
            }
            $edges = $node['edges'] ?? [];
            if (!is_array($edges) || !array_is_list($edges) || count($edges) > 50) {
                $errors[] = "invalid edges for $id";
                continue;
            }
            foreach ($edges as $edge) {
                if (!is_array($edge) || !is_string($edge['to'] ?? null)) {
                    $errors[] = "invalid edge for $id";
                    continue;
                }
                if (isset($edge['when'])) {
                    $when = $edge['when'];
                    if (!is_array($when) || !in_array($when['operator'] ?? null, ['equals', 'not_equals', 'contains'], true) || !is_string($when['value'] ?? null)) {
                        $errors[] = "invalid condition for $id";
                    }
                }
            }
        }
        if (!isset($byId[$start])) {
            $errors[] = 'start node not found';
        }
        $adjacency = [];
        foreach ($byId as $id => $node) {
            $adjacency[$id] = [];
            foreach (($node['edges'] ?? []) as $edge) {
                if (!is_array($edge) || !is_string($edge['to'] ?? null)) {
                    continue;
                }
                if (!isset($byId[$edge['to']])) {
                    $errors[] = "unknown target from $id";
                } else {
                    $adjacency[$id][] = $edge['to'];
                }
            }
        }
        $colors = [];
        $visit = function (string $id) use (&$visit, &$colors, &$errors, $adjacency): void {
            if (($colors[$id] ?? 0) === 1) {
                $errors[] = "cycle detected at $id";
                return;
            }
            if (($colors[$id] ?? 0) === 2) {
                return;
            }
            $colors[$id] = 1;
            foreach ($adjacency[$id] ?? [] as $next) {
                $visit($next);
            }
            $colors[$id] = 2;
        };
        if (isset($byId[$start])) {
            $visit($start);
        }
        foreach (array_keys($byId) as $id) {
            if (!isset($colors[$id])) {
                $errors[] = "unreachable node: $id";
            }
        }
        return array_values(array_unique($errors));
    }
}
