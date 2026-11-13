<?php
declare(strict_types=1);

/** Minimal WordPress stubs: lifecycle contract test without a WP installation. */
final class WP_Error
{
    public function __construct(private string $code, private string $message = '', private array $data = []) {}
    public function get_error_code(): string { return $this->code; }
}
function is_wp_error(mixed $value): bool { return $value instanceof WP_Error; }
$GLOBALS['pmf_test_posts'] = [1 => (object) ['ID' => 1, 'post_type' => 'pmf_flow', 'post_status' => 'draft']];
$GLOBALS['pmf_test_meta'] = [];
function register_post_type(string $name, array $args): void {}
function add_filter(string $name, callable $callback, int $priority = 10, int $args = 1): void {}
function get_post(int $id): ?object { return $GLOBALS['pmf_test_posts'][$id] ?? null; }
function get_post_meta(int $id, string $key, bool $single = false): mixed
{
    return $GLOBALS['pmf_test_meta'][$id][$key] ?? '';
}
function update_post_meta(int $id, string $key, mixed $value): bool
{
    $GLOBALS['pmf_test_meta'][$id][$key] = is_string($value) ? stripslashes($value) : $value;
    return true;
}
function add_post_meta(int $id, string $key, mixed $value, bool $unique = false): bool
{
    if ($unique && isset($GLOBALS['pmf_test_meta'][$id][$key])) { return false; }
    return update_post_meta($id, $key, $value);
}
function delete_post_meta(int $id, string $key): void { unset($GLOBALS['pmf_test_meta'][$id][$key]); }
function wp_json_encode(mixed $value): string|false { return json_encode($value); }
function wp_slash(string $value): string { return addslashes($value); }
function wp_update_post(array $data, bool $returnError = false): int
{
    $GLOBALS['pmf_test_posts'][$data['ID']]->post_status = $data['post_status'];
    return $data['ID'];
}

require __DIR__ . '/../src/Flows/FlowValidator.php';
require __DIR__ . '/../src/Flows/FlowRepository.php';

use PaulMaudry\PipeMyForm\Flows\FlowRepository;

function check(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
}

$flow = [
    'schema_version' => 1,
    'start' => 'intro',
    'nodes' => [
        ['id' => 'intro', 'type' => 'info', 'label' => 'Welcome', 'edges' => []],
    ],
];
$repo = new FlowRepository();
$repo->register();
$attempt = $repo->guardPublication(['post_type' => 'pmf_flow', 'post_status' => 'publish'], ['ID' => 1]);
check($attempt['post_status'] === 'draft', 'WordPress native publish must be blocked');
check($repo->saveDraft(1, json_encode($flow), 0) === 1, 'Save draft revision');
check(is_wp_error($repo->saveDraft(1, json_encode($flow), 0)), 'Stale revision rejected');
check($repo->publish(1, 1) === 1, 'Publish first snapshot');
check($repo->published(1)['nodes'][0]['label'] === 'Welcome', 'Public snapshot exists');
check($repo->guardPublication(['post_type' => 'pmf_flow', 'post_status' => 'publish'], ['ID' => 1])['post_status'] === 'publish', 'Existing published post status remains');

$flow['nodes'][0]['label'] = 'Updated unpublished draft';
check($repo->saveDraft(1, json_encode($flow), 1) === 2, 'Update draft');
check($repo->published(1)['nodes'][0]['label'] === 'Welcome', 'Draft editing cannot overwrite live snapshot');
check($repo->publish(1, 2) === 2, 'Publish next version');
check($repo->published(1)['nodes'][0]['label'] === 'Updated unpublished draft', 'New snapshot visible');
check(json_decode(get_post_meta(1, '_pmf_snapshot_v1', true), true)['nodes'][0]['label'] === 'Welcome', 'Old version preserved');

$invalid = ['schema_version' => 1, 'start' => 'missing', 'nodes' => []];
check($repo->saveDraft(1, json_encode($invalid), 2) === 3, 'Incomplete draft may be saved');
check(is_wp_error($repo->publish(1, 3)), 'Invalid flow cannot publish');
check($repo->published(1)['nodes'][0]['label'] === 'Updated unpublished draft', 'Invalid draft leaves live snapshot unchanged');
echo "Flow repository lifecycle tests passed\n";
