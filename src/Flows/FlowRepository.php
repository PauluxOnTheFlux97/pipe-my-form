<?php
declare(strict_types=1);

namespace PaulMaudry\PipeMyForm\Flows;

final class FlowRepository
{
    public const POST_TYPE = 'pmf_flow';
    public const DRAFT_META = '_pmf_flow_definition';
    public const PUBLISHED_META = '_pmf_published_snapshot';
    public const VERSION_META = '_pmf_published_version';
    public const DRAFT_REVISION_META = '_pmf_draft_revision';

    private static bool $publishing = false;

    public function register(): void
    {
        register_post_type(self::POST_TYPE, [
            'label' => 'Pipe My Form flows',
            'public' => false,
            'show_ui' => true,
            'show_in_rest' => false,
            'supports' => ['title'],
            'capability_type' => 'post',
            'map_meta_cap' => true,
        ]);
        add_filter('wp_insert_post_data', [$this, 'guardPublication'], 10, 2);
    }

    /**
     * Publishing a flow requires validated snapshot creation. The regular WP
     * Publish button cannot bypass that workflow.
     */
    public function guardPublication(array $data, array $postarr): array
    {
        if (($data['post_type'] ?? '') !== self::POST_TYPE
            || ($data['post_status'] ?? '') !== 'publish'
            || self::$publishing) {
            return $data;
        }
        $existing = isset($postarr['ID']) ? get_post((int) $postarr['ID']) : null;
        $data['post_status'] = $existing && $existing->post_status === 'publish'
            ? 'publish'
            : 'draft';
        return $data;
    }

    public function draftRevision(int $id): int
    {
        return max(0, (int) get_post_meta($id, self::DRAFT_REVISION_META, true));
    }

    public function draft(int $id): ?array
    {
        if (!$this->isFlow($id)) {
            return null;
        }
        $raw = get_post_meta($id, self::DRAFT_META, true);
        $parsed = is_string($raw) ? json_decode($raw, true) : null;
        return is_array($parsed) ? $parsed : null;
    }

    public function saveDraft(int $id, string $rawJson, int $expectedRevision): int|\WP_Error
    {
        if (!$this->isFlow($id)) {
            return new \WP_Error('pmf_not_found', 'Flow not found.', ['status' => 404]);
        }
        if (strlen($rawJson) > 131072) {
            return new \WP_Error('pmf_too_large', 'Definition exceeds 128 KiB.', ['status' => 413]);
        }
        $definition = json_decode($rawJson, true);
        if (!is_array($definition) || array_is_list($definition) || json_last_error() !== JSON_ERROR_NONE) {
            return new \WP_Error('pmf_invalid_json', 'Definition must be a JSON object.', ['status' => 400]);
        }
        $revision = $this->draftRevision($id);
        if ($revision !== $expectedRevision) {
            return new \WP_Error('pmf_conflict', 'Draft changed; reload before saving.', ['status' => 409]);
        }
        $encoded = wp_json_encode($definition);
        if (!is_string($encoded)) {
            return new \WP_Error('pmf_encode_error', 'Could not encode definition.', ['status' => 400]);
        }
        if (!update_post_meta($id, self::DRAFT_META, wp_slash($encoded))
            && get_post_meta($id, self::DRAFT_META, true) !== $encoded) {
            return new \WP_Error('pmf_save_error', 'Could not store draft.', ['status' => 500]);
        }
        $revision++;
        update_post_meta($id, self::DRAFT_REVISION_META, $revision);
        return $revision;
    }

    /**
     * Explicitly publish a validated copy. Earlier versions are immutable
     * (separate protected post meta keys) and remain available for audit.
     */
    public function publish(int $id, int $expectedRevision): int|\WP_Error
    {
        $post = get_post($id);
        if (!$post || $post->post_type !== self::POST_TYPE) {
            return new \WP_Error('pmf_not_found', 'Flow not found.', ['status' => 404]);
        }
        if ($this->draftRevision($id) !== $expectedRevision) {
            return new \WP_Error('pmf_conflict', 'Draft changed; reload before publishing.', ['status' => 409]);
        }
        $definition = $this->draft($id);
        if ($definition === null) {
            return new \WP_Error('pmf_missing_draft', 'Save a valid JSON draft first.', ['status' => 400]);
        }
        $errors = (new FlowValidator())->validate($definition);
        if ($errors) {
            return new \WP_Error('pmf_invalid_flow', implode('; ', $errors), ['status' => 422, 'errors' => $errors]);
        }

        $version = max(0, (int) get_post_meta($id, self::VERSION_META, true)) + 1;
        $snapshot = wp_json_encode($definition);
        if (!is_string($snapshot)) {
            return new \WP_Error('pmf_encode_error', 'Could not encode snapshot.', ['status' => 500]);
        }
        // Prevent accidental overwrites of published versions.
        if (!add_post_meta($id, '_pmf_snapshot_v' . $version, wp_slash($snapshot), true)) {
            return new \WP_Error('pmf_version_conflict', 'Version already exists.', ['status' => 409]);
        }

        if ($post->post_status !== 'publish') {
            self::$publishing = true;
            try {
                $result = wp_update_post(['ID' => $id, 'post_status' => 'publish'], true);
            } finally {
                self::$publishing = false;
            }
            if (is_wp_error($result) || !$result) {
                delete_post_meta($id, '_pmf_snapshot_v' . $version);
                return new \WP_Error('pmf_publish_failed', 'Could not publish flow.', ['status' => 500]);
            }
        }
        update_post_meta($id, self::PUBLISHED_META, wp_slash($snapshot));
        update_post_meta($id, self::VERSION_META, $version);
        return $version;
    }

    public function published(int $id): ?array
    {
        $post = get_post($id);
        if (!$post || $post->post_type !== self::POST_TYPE || $post->post_status !== 'publish') {
            return null;
        }
        $raw = get_post_meta($id, self::PUBLISHED_META, true);
        $data = is_string($raw) ? json_decode($raw, true) : null;
        return is_array($data) ? $data : null;
    }

    private function isFlow(int $id): bool
    {
        $post = get_post($id);
        return $post !== null && $post->post_type === self::POST_TYPE;
    }
}
