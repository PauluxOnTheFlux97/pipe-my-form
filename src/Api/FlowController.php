<?php
declare(strict_types=1);

namespace PaulMaudry\PipeMyForm\Api;

use PaulMaudry\PipeMyForm\Flows\FlowRepository;

final class FlowController
{
    public function register(): void
    {
        register_rest_route('pipe-my-form/v1', '/flows/(?P<id>\d+)', [
            'methods' => 'GET',
            'permission_callback' => '__return_true',
            'callback' => function (\WP_REST_Request $request): \WP_REST_Response|\WP_Error {
                $flow = (new FlowRepository())->published(absint($request->get_param('id')));
                return $flow === null
                    ? new \WP_Error('pmf_not_found', 'Flow not found', ['status' => 404])
                    : rest_ensure_response($flow);
            },
        ]);

        register_rest_route('pipe-my-form/v1', '/admin/flows/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'permission_callback' => [$this, 'canEdit'],
                'callback' => [$this, 'getDraft'],
            ],
            [
                'methods' => 'PUT',
                'permission_callback' => [$this, 'canEdit'],
                'callback' => [$this, 'saveDraft'],
            ],
        ]);

        register_rest_route('pipe-my-form/v1', '/admin/flows/(?P<id>\d+)/publish', [
            'methods' => 'POST',
            'permission_callback' => [$this, 'canPublish'],
            'callback' => [$this, 'publish'],
        ]);
    }

    public function canEdit(\WP_REST_Request $request): bool
    {
        $id = absint($request->get_param('id'));
        $post = get_post($id);
        return $post !== null
            && $post->post_type === FlowRepository::POST_TYPE
            && current_user_can('edit_post', $id);
    }

    public function canPublish(\WP_REST_Request $request): bool
    {
        return $this->canEdit($request) && current_user_can('publish_posts');
    }

    public function getDraft(\WP_REST_Request $request): \WP_REST_Response
    {
        $id = absint($request->get_param('id'));
        $repo = new FlowRepository();
        return rest_ensure_response([
            'definition' => $repo->draft($id),
            'revision' => $repo->draftRevision($id),
            'published_version' => (int) get_post_meta($id, FlowRepository::VERSION_META, true),
        ]);
    }

    public function saveDraft(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
    {
        $body = $request->get_json_params();
        if (!is_array($body) || !array_key_exists('definition', $body)
            || !is_array($body['definition']) || array_is_list($body['definition'])
            || !isset($body['expected_revision']) || !is_int($body['expected_revision'])) {
            return new \WP_Error('pmf_invalid_request', 'Expected JSON object definition and integer expected_revision.', ['status' => 400]);
        }
        $encoded = wp_json_encode($body['definition']);
        if (!is_string($encoded)) {
            return new \WP_Error('pmf_invalid_json', 'Invalid definition.', ['status' => 400]);
        }
        $revision = (new FlowRepository())->saveDraft(
            absint($request->get_param('id')),
            $encoded,
            $body['expected_revision']
        );
        return is_wp_error($revision)
            ? $revision
            : rest_ensure_response(['revision' => $revision]);
    }

    public function publish(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
    {
        $body = $request->get_json_params();
        if (!is_array($body) || !isset($body['expected_revision']) || !is_int($body['expected_revision'])) {
            return new \WP_Error('pmf_invalid_request', 'Expected integer expected_revision.', ['status' => 400]);
        }
        $version = (new FlowRepository())->publish(
            absint($request->get_param('id')),
            $body['expected_revision']
        );
        return is_wp_error($version)
            ? $version
            : rest_ensure_response(['published_version' => $version]);
    }
}
