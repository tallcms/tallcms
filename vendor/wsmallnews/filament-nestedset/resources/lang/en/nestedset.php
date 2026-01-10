<?php

return [
    'tree' => [
        'empty_label' => 'No data',
    ],

    'action' => [
        'create_child_node' => 'Create child node',

        'delete_failed_title' => 'Deletion failed',
        'delete_failed_body_has_child' => 'There are child nodes, Please delete child nodes first.',

        'move_node' => 'Move node',
        'move_node_success' => 'Node moved successfully',
        'move_node_failed' => 'Move node failed',
        'move_node_failed_body_depth' => 'Move node failed, the target node level cannot exceed the supported node level :level.',

        'fix_nestedset' => 'Fix nested set',
        'fix_nestedset_success' => 'Nested set fixed successfully',
    ],

    'field' => [
        'parent_select_field' => 'Parent node',
        'parent_select_field_placeholder' => 'Please select a parent node',
        'parent_select_field_empty_label' => 'No parent node found',
    ],
];
