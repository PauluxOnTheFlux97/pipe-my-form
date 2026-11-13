<?php
declare(strict_types=1);
require __DIR__ . '/../src/Flows/FlowValidator.php';
require __DIR__ . '/../src/Flows/FlowEngine.php';

use PaulMaudry\PipeMyForm\Flows\FlowEngine;
use PaulMaudry\PipeMyForm\Flows\FlowValidator;

$flow = [
    'schema_version' => 1,
    'start' => 'intent',
    'nodes' => [
        ['id' => 'intent', 'type' => 'single_choice', 'label' => 'What do you need?', 'required' => true,
            'options' => ['sales', 'support'], 'edges' => [
                ['to' => 'sales', 'when' => ['operator' => 'equals', 'value' => 'sales']],
                ['to' => 'support'],
            ]],
        ['id' => 'sales', 'type' => 'email', 'label' => 'Email', 'required' => true, 'edges' => []],
        ['id' => 'support', 'type' => 'info', 'label' => 'Visit support', 'edges' => []],
    ],
];
$validator = new FlowValidator();
assert($validator->validate($flow) === []);
$engine = new FlowEngine();
assert($engine->next($flow, 'intent', 'sales')['id'] === 'sales');
assert($engine->next($flow, 'intent', 'support')['id'] === 'support');
assert($engine->next($flow, 'sales', 'test@example.com') === null);
$invalid = $flow;
$invalid['nodes'][2]['edges'] = [['to' => 'intent']];
assert($validator->validate($invalid) !== []);
$failed = false;
try { $engine->next($flow, 'sales', 'invalid-email'); } catch (InvalidArgumentException $e) { $failed = true; }
assert($failed);
echo "Flow engine smoke tests passed\n";
