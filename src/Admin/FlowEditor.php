<?php
declare(strict_types=1);

namespace PaulMaudry\PipeMyForm\Admin;

use PaulMaudry\PipeMyForm\Flows\FlowRepository;

final class FlowEditor
{
    public function register(): void
    {
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_post_pmf_save_flow', [$this, 'handleSave']);
        add_filter('post_row_actions', [$this, 'rowActions'], 10, 2);
    }

    public function menu(): void
    {
        add_submenu_page(
            'edit.php?post_type=' . FlowRepository::POST_TYPE,
            'Flow JSON editor',
            'Edit flow JSON',
            'edit_posts',
            'pmf-flow-editor',
            [$this, 'render']
        );
    }

    public function rowActions(array $actions, \WP_Post $post): array
    {
        if ($post->post_type === FlowRepository::POST_TYPE && current_user_can('edit_post', $post->ID)) {
            $url = add_query_arg(
                ['page' => 'pmf-flow-editor', 'id' => $post->ID],
                admin_url('edit.php?post_type=' . FlowRepository::POST_TYPE)
            );
            $actions['pmf_editor'] = '<a href="' . esc_url($url) . '">Edit flow JSON</a>';
        }
        return $actions;
    }

    public function render(): void
    {
        if (!current_user_can('edit_posts')) {
            wp_die('Permission denied.');
        }
        $id = isset($_GET['id']) ? absint($_GET['id']) : 0;
        $post = $id ? get_post($id) : null;
        echo '<div class="wrap"><h1>Pipe My Form — Flow editor</h1>';
        if (!$post || $post->post_type !== FlowRepository::POST_TYPE || !current_user_can('edit_post', $id)) {
            echo '<p>Create a new flow using <a href="' . esc_url(admin_url('post-new.php?post_type=' . FlowRepository::POST_TYPE)) . '">Add New</a>, save it as a draft, then use the “Edit flow JSON” action on the flows list.</p></div>';
            return;
        }

        $repo = new FlowRepository();
        $raw = get_post_meta($id, FlowRepository::DRAFT_META, true);
        $default = "{\n  \"schema_version\": 1,\n  \"start\": \"start\",\n  \"nodes\": [\n    {\"id\":\"start\",\"type\":\"info\",\"label\":\"Welcome\",\"edges\":[]}\n  ]\n}";
        $raw = is_string($raw) && $raw !== '' ? $raw : $default;
        $revision = $repo->draftRevision($id);
        $version = (int) get_post_meta($id, FlowRepository::VERSION_META, true);

        $status = isset($_GET['pmf_status']) ? sanitize_key(wp_unslash($_GET['pmf_status'])) : '';
        $messages = [
            'saved' => 'Draft saved.',
            'published' => 'Validated snapshot published.',
            'conflict' => 'Another edit was saved. Reload before retrying.',
            'invalid_json' => 'Invalid JSON; draft was not saved.',
            'invalid_flow' => 'Flow validation failed; correct the nodes and edges before publishing.',
            'error' => 'The requested action failed.',
        ];
        if (isset($messages[$status])) {
            $class = in_array($status, ['saved', 'published'], true) ? 'notice-success' : 'notice-error';
            echo '<div class="notice ' . esc_attr($class) . '"><p>' . esc_html($messages[$status]) . '</p></div>';
        }

        echo '<p><strong>' . esc_html(get_the_title($id)) . '</strong> · Draft revision ' . esc_html((string) $revision)
            . ' · Published version ' . esc_html((string) $version) . '</p>';
        echo '<p>JSON editor (M1 prototype). Use the versioned schema in <code>docs/FLOW-SCHEMA.md</code>. A published flow keeps serving the last validated snapshot while you edit its draft.</p>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="pmf_save_flow">';
        echo '<input type="hidden" name="flow_id" value="' . esc_attr((string) $id) . '">';
        echo '<input type="hidden" name="expected_revision" value="' . esc_attr((string) $revision) . '">';
        wp_nonce_field('pmf_edit_flow_' . $id);
        echo '<label for="pmf_definition"><strong>Flow definition (JSON)</strong></label>';
        echo '<textarea id="pmf_definition" name="pmf_definition" rows="22" class="large-text code" spellcheck="false" maxlength="131072">' . esc_textarea($raw) . '</textarea>';
        echo '<p><button class="button button-primary" type="submit" name="pmf_intent" value="save">Save draft</button> ';
        if (current_user_can('publish_posts')) {
            echo '<button class="button" type="submit" name="pmf_intent" value="publish">Save and publish validated snapshot</button>';
        }
        echo '</p></form></div>';
    }

    public function handleSave(): void
    {
        $id = isset($_POST['flow_id']) ? absint($_POST['flow_id']) : 0;
        $post = $id ? get_post($id) : null;
        if (!$post || $post->post_type !== FlowRepository::POST_TYPE || !current_user_can('edit_post', $id)) {
            wp_die('Permission denied.', '', ['response' => 403]);
        }
        check_admin_referer('pmf_edit_flow_' . $id);
        $intent = isset($_POST['pmf_intent']) ? sanitize_key(wp_unslash($_POST['pmf_intent'])) : 'save';
        if ($intent === 'publish' && !current_user_can('publish_posts')) {
            wp_die('Publish permission denied.', '', ['response' => 403]);
        }
        $raw = isset($_POST['pmf_definition']) && is_string($_POST['pmf_definition'])
            ? wp_unslash($_POST['pmf_definition']) : '';
        $revision = isset($_POST['expected_revision']) ? absint($_POST['expected_revision']) : 0;

        $repo = new FlowRepository();
        $saved = $repo->saveDraft($id, $raw, $revision);
        $status = 'saved';
        if (is_wp_error($saved)) {
            $status = $saved->get_error_code() === 'pmf_conflict' ? 'conflict'
                : ($saved->get_error_code() === 'pmf_invalid_json' ? 'invalid_json' : 'error');
        } elseif ($intent === 'publish') {
            $result = $repo->publish($id, $saved);
            $status = is_wp_error($result)
                ? ($result->get_error_code() === 'pmf_invalid_flow' ? 'invalid_flow' : 'error')
                : 'published';
        }
        $url = add_query_arg([
            'post_type' => FlowRepository::POST_TYPE,
            'page' => 'pmf-flow-editor',
            'id' => $id,
            'pmf_status' => $status,
        ], admin_url('edit.php'));
        wp_safe_redirect($url);
        exit;
    }
}
