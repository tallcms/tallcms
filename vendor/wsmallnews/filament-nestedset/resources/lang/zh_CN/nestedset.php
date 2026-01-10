<?php

return [
    'tree' => [
        'empty_label' => '没有数据',
    ],

    'action' => [
        'create_child_node' => '创建子节点',

        'delete_failed_title' => '删除失败',
        'delete_failed_body_has_child' => '存在子级节点, 请先删除子级节点.',

        'move_node' => '移动节点',
        'move_node_success' => '节点移动成功',
        'move_node_failed' => '节点移动失败',
        'move_node_failed_body_depth' => '节点移动失败, 目标节点层级不能超过支持节点层级 :level.',

        'fix_nestedset' => '修复嵌套集合',
        'fix_nestedset_success' => '嵌套集合修复成功',
    ],

    'field' => [
        'parent_select_field' => '父节点',
        'parent_select_field_placeholder' => '请选择父节点',
        'parent_select_field_empty_label' => '未搜索到父节点',
    ],
];
