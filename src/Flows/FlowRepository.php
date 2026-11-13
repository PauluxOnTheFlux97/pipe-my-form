<?php
declare(strict_types=1);

namespace PaulMaudry\PipeMyForm\Flows;

final class FlowRepository
{
    public const POST_TYPE = 'pmf_flow';
    public const DRAFT_META = '_pmf_flow_definition';
    public const PUBLISHED_META = '_pmf_published_snapshot';

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
        add_action('transition_post_status', [$this, 'onTransition'], 10, 3);
    }

    public function onTransition(string $newStatus, string $oldStatus, \WP_Post $post): void
    {
        if ($post->post_type !== self::POST_TYPE || $newStatus !== 'publish' || $oldStatus === 'publish') {
            return;
        }
        $raw = get_post_meta($post->ID, self::DRAFT_META, true);
        $definition = is_string($raw) ? json_decode($raw, true) : $raw;
        if (!is_array($definition) || (new FlowValidator())->validate($definition)) {
            // Reject invalid publication rather than expose an incomplete flow.
            remove_action('transition_post_status', [$this, 'onTransition'], 10);
            wp_update_post(['ID' => $post->ID, 'post_status' => 'draft']);
            add_action('transition_post_status', [$this, 'onTransition'], 10, 3);
            return;
        }
        update_post_meta($post->ID, self::PUBLISHED_META, wp_json_encode($definition));
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
}
