<?php
declare(strict_types=1);

namespace PaulMaudry\PipeMyForm\Api;

use PaulMaudry\PipeMyForm\Flows\FlowRepository;

final class FlowController
{
    public function register(): void
    {
        register_rest_route('pipe-my-form/v1', '/flows/(?P<id>\\d+)', [
            'methods' => 'GET',
            'permission_callback' => '__return_true',
            'callback' => function (\WP_REST_Request $request): \WP_REST_Response|\WP_Error {
                $id = absint($request->get_param('id'));
                $flow = (new FlowRepository())->published($id);
                if ($flow === null) {
                    return new \WP_Error('pmf_not_found', 'Flow not found', ['status' => 404]);
                }
                return rest_ensure_response($flow);
            },
        ]);
    }
}
