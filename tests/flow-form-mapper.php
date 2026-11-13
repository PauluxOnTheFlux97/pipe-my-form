<?php
declare(strict_types=1);

require __DIR__ . '/../src/Flows/FlowFormMapper.php';
require __DIR__ . '/../src/Flows/FlowValidator.php';

use PaulMaudry\PipeMyForm\Flows\FlowFormMapper;
use PaulMaudry\PipeMyForm\Flows\FlowValidator;

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$input = [
    'start' => 'intro',
    'nodes' => [
        '7' => [
            'id' => 'intro',
            'type' => 'single_choice',
            'label' => 'Choose a service',
            'required' => '1',
            'options' => "sales\r\nsupport\n",
            'edges' => [
                '9' => ['to' => 'sales', 'operator' => 'equals', 'value' => 'sales'],
                '15' => ['to' => 'support', 'operator' => '', 'value' => ''],
            ],
        ],
        '10' => ['id' => 'sales', 'type' => 'email', 'label' => 'Your email', 'edges' => []],
        '13' => ['id' => 'support', 'type' => 'info', 'label' => 'Contact support', 'edges' => []],
    ],
];
$mapper = new FlowFormMapper();
$flow = $mapper->fromInput($input);
check($flow['schema_version'] === 1, 'Schema version retained');
check($flow['nodes'][0]['options'] === ['sales', 'support'], 'Choice options normalized');
check($flow['nodes'][0]['required'] === true, 'Required flag set');
check($flow['nodes'][0]['edges'][0]['when'] === ['operator' => 'equals', 'value' => 'sales'], 'Conditional edge retained');
check(!isset($flow['nodes'][0]['edges'][1]['when']), 'Fallback stays unconditional');
check((new FlowValidator())->validate($flow) === [], 'Mapped flow validates');

$invalid = $input;
$invalid['nodes']['7']['type'] = 'script';
$threw = false;
try { $mapper->fromInput($invalid); } catch (InvalidArgumentException $e) { $threw = true; }
check($threw, 'Unknown type rejected');

$invalid = $input;
$invalid['nodes']['7']['label'] = str_repeat('x', 1001);
$threw = false;
try { $mapper->fromInput($invalid); } catch (InvalidArgumentException $e) { $threw = true; }
check($threw, 'Oversized field rejected');

$input['nodes']['7']['edges'][] = ['to' => '', 'operator' => '', 'value' => ''];
check(count($mapper->fromInput($input)['nodes'][0]['edges']) === 2, 'Blank destination skipped');
echo "Structured flow form tests passed\n";
