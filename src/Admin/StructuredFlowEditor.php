<?php
declare(strict_types=1);

namespace PaulMaudry\PipeMyForm\Admin;

use PaulMaudry\PipeMyForm\Flows\FlowFormMapper;
use PaulMaudry\PipeMyForm\Flows\FlowRepository;
use PaulMaudry\PipeMyForm\Flows\FlowValidator;

final class StructuredFlowEditor
{
    public function register(): void
    {
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_enqueue_scripts', [$this, 'assets']);
        add_action('admin_post_pmf_save_structured_flow', [$this, 'handleSave']);
        add_filter('post_row_actions', [$this, 'rowActions'], 10, 2);
    }

    public function menu(): void
    {
        add_submenu_page(
            'edit.php?post_type=' . FlowRepository::POST_TYPE,
            'Flow Builder',
            'Flow Builder',
            'edit_posts',
            'pmf-flow-builder',
            [$this, 'render']
        );
    }

    public function assets(string $hook): void
    {
        if (!isset($_GET['page']) || sanitize_key(wp_unslash($_GET['page'])) !== 'pmf-flow-builder') {
            return;
        }
        wp_enqueue_script(
            'pmf-flow-builder',
            plugins_url('frontend/admin/flow-builder.js', PMF_PLUGIN_FILE),
            [],
            PMF_VERSION,
            true
        );
    }

    public function rowActions(array $actions, \WP_Post $post): array
    {
        if ($post->post_type === FlowRepository::POST_TYPE && current_user_can('edit_post', $post->ID)) {
            $url = add_query_arg(
                ['page' => 'pmf-flow-builder', 'id' => $post->ID],
                admin_url('edit.php?post_type=' . FlowRepository::POST_TYPE)
            );
            $actions['pmf_builder'] = '<a href="' . esc_url($url) . '">Flow Builder</a>';
        }
        return $actions;
    }

    public function render(): void
    {
        $id = isset($_GET['id']) ? absint($_GET['id']) : 0;
        $post = $id ? get_post($id) : null;
        echo '<div class="wrap"><h1>Pipe My Form — Flow Builder</h1>';
        if (!$post || $post->post_type !== FlowRepository::POST_TYPE || !current_user_can('edit_post', $id)) {
            echo '<p>Create a flow and save it as a draft using <a href="' . esc_url(admin_url('post-new.php?post_type=' . FlowRepository::POST_TYPE)) . '">Add New</a>. Then use Flow Builder in its row actions.</p></div>';
            return;
        }
        $repo = new FlowRepository();
        $draft = $repo->draft($id) ?? [
            'schema_version' => 1,
            'start' => 'welcome',
            'nodes' => [
                ['id' => 'welcome', 'type' => 'info', 'label' => 'Welcome', 'required' => false, 'edges' => []],
            ],
        ];
        $revision = $repo->draftRevision($id);
        $version = (int) get_post_meta($id, FlowRepository::VERSION_META, true);
        $status = isset($_GET['pmf_status']) ? sanitize_key(wp_unslash($_GET['pmf_status'])) : '';
        $messages = [
            'saved' => 'Draft saved. Publish when the flow is complete.',
            'published' => 'Validated flow version published.',
            'conflict' => 'The draft was changed elsewhere. Reload before saving.',
            'invalid' => 'The form contains invalid fields or an invalid flow graph.',
            'error' => 'Save failed. Check inputs and retry.',
        ];
        if (isset($messages[$status])) {
            $class = in_array($status, ['saved', 'published'], true) ? 'notice-success' : 'notice-error';
            echo '<div class="notice ' . esc_attr($class) . '"><p>' . esc_html($messages[$status]) . '</p></div>';
        }

        echo '<p><strong>' . esc_html(get_the_title($id)) . '</strong> · Draft revision ' . esc_html((string) $revision)
            . ' · Published version ' . esc_html((string) $version) . '</p>';
        echo '<p>Define your questions and connect them. Conditions are evaluated in connection order, followed by the first unconditional fallback. You can save an incomplete draft, but publication requires a valid, reachable, acyclic flow.</p>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" id="pmf-builder-form">';
        echo '<input type="hidden" name="action" value="pmf_save_structured_flow">';
        echo '<input type="hidden" name="flow_id" value="' . esc_attr((string) $id) . '">';
        echo '<input type="hidden" name="expected_revision" value="' . esc_attr((string) $revision) . '">';
        wp_nonce_field('pmf_edit_flow_' . $id);

        echo '<p><label for="pmf-start"><strong>Starting question ID</strong></label> ';
        echo '<input id="pmf-start" type="text" name="start" value="' . esc_attr((string) ($draft['start'] ?? '')) . '" required pattern="[a-z][a-z0-9_-]*"></p>';
        echo '<div id="pmf-builder-nodes">';
        foreach (($draft['nodes'] ?? []) as $index => $node) {
            if (is_array($node)) {
                $this->renderNode((string) $index, $node);
            }
        }
        echo '</div><p><button type="button" id="pmf-add-node" class="button">+ Add question</button></p>';
        echo '<p><button type="submit" class="button button-primary" name="pmf_intent" value="save">Save draft</button> ';
        if (current_user_can('publish_posts')) {
            echo '<button type="submit" class="button" name="pmf_intent" value="publish">Save &amp; publish</button>';
        }
        echo ' <a class="button" href="' . esc_url(add_query_arg(['page' => 'pmf-flow-editor', 'id' => $id], admin_url('edit.php?post_type=' . FlowRepository::POST_TYPE))) . '">Advanced JSON editor</a></p></form></div>';
    }

    private function renderNode(string $key, array $node): void
    {
        $root = 'nodes[' . $key . ']';
        $id = (string) ($node['id'] ?? '');
        $type = (string) ($node['type'] ?? 'text');
        $label = (string) ($node['label'] ?? '');
        $required = !empty($node['required']);
        $options = isset($node['options']) && is_array($node['options']) ? implode("\n", $node['options']) : '';
        echo '<fieldset class="pmf-node" style="background:#fff;border:1px solid #c3c4c7;padding:16px;margin:12px 0;max-width:1000px">';
        echo '<legend><strong>Question</strong></legend>';
        echo '<p><label>Unique ID <input class="regular-text" required name="' . esc_attr($root . '[id]') . '" value="' . esc_attr($id) . '"></label> ';
        echo '<label>Type <select name="' . esc_attr($root . '[type]') . '">';
        foreach (['text', 'textarea', 'email', 'tel', 'single_choice', 'multiple_choice', 'consent', 'info'] as $option) {
            echo '<option value="' . esc_attr($option) . '"' . selected($type, $option, false) . '>' . esc_html($option) . '</option>';
        }
        echo '</select></label></p>';
        echo '<p><label>Question / screen text <input style="min-width:65%" required name="' . esc_attr($root . '[label]') . '" value="' . esc_attr($label) . '"></label></p>';
        echo '<p><label><input type="checkbox" name="' . esc_attr($root . '[required]') . '" value="1"' . checked($required, true, false) . '> Required</label></p>';
        echo '<p><label>Answer options, one per line (single or multiple choice)<br><textarea rows="3" style="width:100%;max-width:700px" name="' . esc_attr($root . '[options]') . '">' . esc_textarea($options) . '</textarea></label></p>';
        echo '<strong>Connections</strong> <small>(empty destination = finish; leave operator blank for fallback)</small>';
        echo '<div class="pmf-edges">';
        $edges = isset($node['edges']) && is_array($node['edges']) ? $node['edges'] : [];
        foreach ($edges as $edgeIndex => $edge) {
            if (is_array($edge)) {
                $this->renderEdge($root, (string) $edgeIndex, $edge);
            }
        }
        echo '</div><p><button type="button" class="button pmf-add-edge">+ Add connection</button></p>';
        echo '<p><button type="button" class="button pmf-move-up">Move up</button> <button type="button" class="button pmf-move-down">Move down</button> <button type="button" class="button-link-delete pmf-delete-node">Remove question</button></p>';
        echo '</fieldset>';
    }

    private function renderEdge(string $root, string $index, array $edge): void
    {
        $prefix = $root . '[edges][' . $index . ']';
        $condition = $edge['when'] ?? [];
        $operator = is_array($condition) ? (string) ($condition['operator'] ?? '') : '';
        $value = is_array($condition) ? (string) ($condition['value'] ?? '') : '';
        echo '<div class="pmf-edge" style="margin:8px 0">';
        echo '<label>Go to ID <input name="' . esc_attr($prefix . '[to]') . '" value="' . esc_attr((string) ($edge['to'] ?? '')) . '" placeholder="next_question"></label> ';
        echo '<label>When <select name="' . esc_attr($prefix . '[operator]') . '">';
        foreach (['' => 'Always (fallback)', 'equals' => 'Equals', 'not_equals' => 'Does not equal', 'contains' => 'Contains'] as $key => $name) {
            echo '<option value="' . esc_attr($key) . '"' . selected($operator, $key, false) . '>' . esc_html($name) . '</option>';
        }
        echo '</select></label> ';
        echo '<label>Value <input name="' . esc_attr($prefix . '[value]') . '" value="' . esc_attr($value) . '"></label> ';
        echo '<button type="button" class="button-link-delete pmf-delete-edge">Remove</button></div>';
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
        if (!in_array($intent, ['save', 'publish'], true)) {
            wp_die('Invalid action.', '', ['response' => 400]);
        }
        if ($intent === 'publish' && !current_user_can('publish_posts')) {
            wp_die('Permission denied.', '', ['response' => 403]);
        }
        $revision = isset($_POST['expected_revision']) ? absint($_POST['expected_revision']) : 0;
        $repo = new FlowRepository();
        $status = 'error';
        try {
            $input = wp_unslash($_POST);
            $definition = (new FlowFormMapper())->fromInput($input);
            $encoded = wp_json_encode($definition);
            if (!is_string($encoded)) {
                throw new \InvalidArgumentException('Could not encode flow.');
            }
            // Fail fast on broken graphs only when publishing, not on draft save.
            if ($intent === 'publish' && (new FlowValidator())->validate($definition)) {
                $status = 'invalid';
            } else {
                $saved = $repo->saveDraft($id, $encoded, $revision);
                if (is_wp_error($saved)) {
                    $status = $saved->get_error_code() === 'pmf_conflict' ? 'conflict' : 'error';
                } elseif ($intent === 'publish') {
                    $published = $repo->publish($id, $saved);
                    $status = is_wp_error($published) ? 'invalid' : 'published';
                } else {
                    $status = 'saved';
                }
            }
        } catch (\InvalidArgumentException $exception) {
            $status = 'invalid';
        }
        wp_safe_redirect(add_query_arg([
            'post_type' => FlowRepository::POST_TYPE,
            'page' => 'pmf-flow-builder',
            'id' => $id,
            'pmf_status' => $status,
        ], admin_url('edit.php')));
        exit;
    }
}
