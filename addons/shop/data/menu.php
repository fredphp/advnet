<?php
$menu = [
    [
        'name'    => 'shop',
        'title'   => '简单商城',
        'icon'    => 'fa fa-shopping-bag',
        'sublist' => [
            [
                'name'    => 'shop/goods',
                'title'   => '商品管理',
                'icon'    => 'fa fa-shopping-basket',
                'ismenu'  => 1,
                'weigh'   => 50,
                'sublist' =>
                    [
                        [
                            'name'  => 'shop/goods/index',
                            'title' => '查看',
                        ],
                        [
                            'name'  => 'shop/goods/recyclebin',
                            'title' => '回收站',
                        ],
                        [
                            'name'  => 'shop/goods/add',
                            'title' => '添加',
                        ],
                        [
                            'name'  => 'shop/goods/edit',
                            'title' => '编辑',
                        ],
                        [
                            'name'  => 'shop/goods/del',
                            'title' => '删除',
                        ],
                        [
                            'name'  => 'shop/goods/destroy',
                            'title' => '真实删除',
                        ],
                        [
                            'name'  => 'shop/goods/restore',
                            'title' => '还原',
                        ],
                        [
                            'name'  => 'shop/goods/multi',
                            'title' => '批量更新',
                        ],
                        [
                            'name'  => 'shop/goods/select',
                            'title' => '选择',
                        ]
                    ],
            ],
            [
                'name'    => 'shop/goods_sku',
                'title'   => '商品属性',
                'icon'    => 'fa fa-line-chart',
                'ismenu'  => 0,
                'weigh'   => 50,
                'sublist' =>
                    [
                        [
                            'name'  => 'shop/goods_sku/index',
                            'title' => '查看',
                        ],
                        [
                            'name'  => 'shop/goods_sku/add',
                            'title' => '添加',
                        ],
                        [
                            'name'  => 'shop/goods_sku/edit',
                            'title' => '编辑',
                        ],
                        [
                            'name'  => 'shop/goods_sku/del',
                            'title' => '删除',
                        ],
                        [
                            'name'  => 'shop/goods_sku/multi',
                            'title' => '批量更新',
                        ],
                    ],
            ],
            [
                'name'    => 'shop/goods_sku_spec',
                'title'   => '商品规格属性',
                'icon'    => 'fa fa-line-chart',
                'ismenu'  => 0,
                'weigh'   => 50,
                'sublist' =>
                    [
                        [
                            'name'  => 'shop/goods_sku_spec/index',
                            'title' => '查看',
                        ],
                        [
                            'name'  => 'shop/goods_sku_spec/add',
                            'title' => '添加',
                        ],
                        [
                            'name'  => 'shop/goods_sku_spec/edit',
                            'title' => '编辑',
                        ],
                        [
                            'name'  => 'shop/goods_sku_spec/del',
                            'title' => '删除',
                        ],
                        [
                            'name'  => 'shop/goods_sku_spec/multi',
                            'title' => '批量更新',
                        ],
                    ],
            ],
            [
                'name'    => 'shop/spec',
                'title'   => '商品规格',
                'icon'    => 'fa fa-pencil',
                'ismenu'  => 0,
                'weigh'   => 50,
                'sublist' =>
                    [
                        [
                            'name'  => 'shop/spec/index',
                            'title' => '查看',
                        ],
                        [
                            'name'  => 'shop/spec/add',
                            'title' => '添加',
                        ],
                        [
                            'name'  => 'shop/spec/edit',
                            'title' => '编辑',
                        ],
                        [
                            'name'  => 'shop/spec/del',
                            'title' => '删除',
                        ],
                        [
                            'name'  => 'shop/spec/multi',
                            'title' => '批量更新',
                        ],
                    ],
            ],
            [
                'name'    => 'shop/spec_value',
                'title'   => '商品规格值',
                'ismenu'  => 0,
                'weigh'   => 50,
                'sublist' =>
                    [
                        [
                            'name'  => 'shop/spec_value/index',
                            'title' => '查看',
                        ],
                        [
                            'name'  => 'shop/spec_value/add',
                            'title' => '添加',
                        ],
                        [
                            'name'  => 'shop/spec_value/edit',
                            'title' => '编辑',
                        ],
                        [
                            'name'  => 'shop/spec_value/del',
                            'title' => '删除',
                        ],
                        [
                            'name'  => 'shop/spec_value/multi',
                            'title' => '批量更新',
                        ],
                    ],
            ],
            [
                'name'    => 'shop/sku_template',
                'title'   => '规格模板',
                'icon'    => 'fa fa-asterisk',
                'ismenu'  => 0,
                'weigh'   => 49,
                'sublist' =>
                    [
                        [
                            'name'  => 'shop/sku_template/index',
                            'title' => '查看',
                        ],
                        [
                            'name'  => 'shop/sku_template/add',
                            'title' => '添加',
                        ],
                        [
                            'name'  => 'shop/sku_template/edit',
                            'title' => '编辑',
                        ],
                        [
                            'name'  => 'shop/sku_template/del',
                            'title' => '删除',
                        ],
                        [
                            'name'  => 'shop/sku_template/multi',
                            'title' => '批量更新',
                        ],
                    ],
            ],
            [
                'name'    => 'shop/guarantee',
                'title'   => '服务保障',
                'icon'    => 'fa fa-asterisk',
                'ismenu'  => 0,
                'weigh'   => 49,
                'sublist' =>
                    [
                        [
                            'name'  => 'shop/guarantee/index',
                            'title' => '查看',
                        ],
                        [
                            'name'  => 'shop/guarantee/add',
                            'title' => '添加',
                        ],
                        [
                            'name'  => 'shop/guarantee/edit',
                            'title' => '编辑',
                        ],
                        [
                            'name'  => 'shop/guarantee/del',
                            'title' => '删除',
                        ],
                        [
                            'name'  => 'shop/guarantee/multi',
                            'title' => '批量更新',
                        ],
                    ],
            ],
            [
                'name'    => 'shop/freight',
                'title'   => '运费模板',
                'icon'    => 'fa fa-sticky-note-o',
                'ismenu'  => 1,
                'weigh'   => 49,
                'sublist' =>
                    [
                        [
                            'name'  => 'shop/freight/index',
                            'title' => '查看',
                        ],
                        [
                            'name'  => 'shop/freight/add',
                            'title' => '添加',
                        ],
                        [
                            'name'  => 'shop/freight/edit',
                            'title' => '编辑',
                        ],
                        [
                            'name'  => 'shop/freight/del',
                            'title' => '删除',
                        ],
                        [
                            'name'  => 'shop/freight/multi',
                            'title' => '批量更新',
                        ],
                    ],
            ],
            [
                'name'    => 'shop/freight_items',
                'title'   => '运费模板值',
                'ismenu'  => 0,
                'weigh'   => 49,
                'sublist' =>
                    [
                        [
                            'name'  => 'shop/freight_items/index',
                            'title' => '查看',
                        ],
                        [
                            'name'  => 'shop/freight_items/add',
                            'title' => '添加',
                        ],
                        [
                            'name'  => 'shop/freight_items/edit',
                            'title' => '编辑',
                        ],
                        [
                            'name'  => 'shop/freight_items/del',
                            'title' => '删除',
                        ],
                        [
                            'name'  => 'shop/freight_items/multi',
                            'title' => '批量更新',
                        ],
                    ],
            ],
            [
                'name'    => 'shop/order',
                'title'   => '订单管理',
                'icon'    => 'fa fa-align-left',
                'ismenu'  => 1,
                'weigh'   => 48,
                'sublist' =>
                    [
                        [
                            'name'  => 'shop/order/index',
                            'title' => '查看',
                        ],
                        [
                            'name'  => 'shop/order/recyclebin',
                            'title' => '回收站',
                        ],
                        [
                            'name'  => 'shop/order/add',
                            'title' => '添加',
                        ],
                        [
                            'name'  => 'shop/order/edit',
                            'title' => '编辑',
                        ],
                        [
                            'name'  => 'shop/order/del',
                            'title' => '删除',
                        ],
                        [
                            'name'  => 'shop/order/destroy',
                            'title' => '真实删除',
                        ],
                        [
                            'name'  => 'shop/order/restore',
                            'title' => '还原',
                        ],
                        [
                            'name'  => 'shop/order/multi',
                            'title' => '批量更新',
                        ],
                        [
                            'name'  => 'shop/order/deliver',
                            'title' => '发货',
                        ],
                        [
                            'name'  => 'shop/order/edit_info',
                            'title' => '编辑订单信息',
                        ],
                        [
                            'name'  => 'shop/order/refund',
                            'title' => '同意退款',
                        ],
                        [
                            'name'  => 'shop/order/edit_status',
                            'title' => '订单状态编辑',
                        ],
                        [
                            'name'  => 'shop/order/detail',
                            'title' => '订单详情',
                        ],
                        [
                            'name'  => 'shop/order/cancel_electronics',
                            'title' => '取消电子面单',
                        ],
                        [
                            'name'  => 'shop/order/electronics',
                            'title' => '打印电子面单',
                        ],
                        [
                            'name'  => 'shop/order/prints',
                            'title' => '批量打印电子面单',
                        ],
                        [
                            'name'  => 'shop/order/orderList',
                            'title' => '批量打印发货单',
                        ],

                    ],
            ],
            [
                'name'    => 'shop/order_goods',
                'title'   => '订单商品',
                'ismenu'  => 0,
                'sublist' =>
                    [
                        [
                            'name'  => 'shop/order_goods/index',
                            'title' => '查看',
                        ],
                        [
                            'name'  => 'shop/order_goods/add',
                            'title' => '添加',
                        ],
                        [
                            'name'  => 'shop/order_goods/edit',
                            'title' => '编辑',
                        ],
                        [
                            'name'  => 'shop/order_goods/del',
                            'title' => '删除',
                        ],
                        [
                            'name'  => 'shop/order_goods/multi',
                            'title' => '批量更新',
                        ],
                    ],
            ],
            [
                'name'    => 'shop/order_action',
                'title'   => '订单操作记录',
                'ismenu'  => 0,
                'sublist' =>
                    [
                        [
                            'name'  => 'shop/order_action/index',
                            'title' => '查看',
                        ],
                        [
                            'name'  => 'shop/order_action/add',
                            'title' => '添加',
                        ],
                        [
                            'name'  => 'shop/order_action/edit',
                            'title' => '编辑',
                        ],
                        [
                            'name'  => 'shop/order_action/del',
                            'title' => '删除',
                        ],
                        [
                            'name'  => 'shop/order_action/multi',
                            'title' => '批量更新',
                        ],
                    ],
            ],
            [
                'name'    => 'shop/comment',
                'title'   => '评论管理',
                'icon'    => 'fa fa-commenting-o',
                'ismenu'  => 1,
                'weigh'   => 47,
                'sublist' =>
                    [
                        [
                            'name'  => 'shop/comment/index',
                            'title' => '查看',
                        ],
                        [
                            'name'  => 'shop/comment/add',
                            'title' => '添加',
                        ],
                        [
                            'name'  => 'shop/comment/edit',
                            'title' => '编辑',
                        ],
                        [
                            'name'  => 'shop/comment/del',
                            'title' => '删除',
                        ],
                        [
                            'name'  => 'shop/comment/multi',
                            'title' => '批量更新',
                        ],
                        [
                            'name'  => 'shop/comment/reply',
                            'title' => '回复',
                        ],
                    ],
            ],
            [
                'name'    => 'shop/shipper',
                'title'   => '快递公司',
                'icon'    => 'fa fa-truck',
                'ismenu'  => 0,
                'weigh'   => 47,
                'sublist' =>
                    [
                        [
                            'name'  => 'shop/shipper/index',
                            'title' => '查看',
                        ],
                        [
                            'name'  => 'shop/shipper/add',
                            'title' => '添加',
                        ],
                        [
                            'name'  => 'shop/shipper/edit',
                            'title' => '编辑',
                        ],
                        [
                            'name'  => 'shop/shipper/del',
                            'title' => '删除',
                        ],
                        [
                            'name'  => 'shop/shipper/multi',
                            'title' => '批量更新',
                        ],
                    ],
            ],
            [
                'name'    => 'shop/electronics_order',
                'title'   => '电子面单',
                'icon'    => 'fa fa-sticky-note-o',
                'ismenu'  => 1,
                'weigh'   => 47,
                'sublist' =>
                    [
                        [
                            'name'  => 'shop/electronics_order/index',
                            'title' => '查看',
                        ],
                        [
                            'name'  => 'shop/electronics_order/add',
                            'title' => '添加',
                        ],
                        [
                            'name'  => 'shop/electronics_order/edit',
                            'title' => '编辑',
                        ],
                        [
                            'name'  => 'shop/electronics_order/del',
                            'title' => '删除',
                        ],
                        [
                            'name'  => 'shop/electronics_order/multi',
                            'title' => '批量更新',
                        ],
                    ],
            ],

            [
                'name'    => 'shop/collect',
                'title'   => '收藏管理',
                'icon'    => 'fa fa-heart',
                'ismenu'  => 1,
                'weigh'   => 46,
                'sublist' =>
                    [
                        [
                            'name'  => 'shop/collect/index',
                            'title' => '查看',
                        ],
                        [
                            'name'  => 'shop/collect/add',
                            'title' => '添加',
                        ],
                        [
                            'name'  => 'shop/collect/edit',
                            'title' => '编辑',
                        ],
                        [
                            'name'  => 'shop/collect/del',
                            'title' => '删除',
                        ],
                        [
                            'name'  => 'shop/collect/multi',
                            'title' => '批量更新',
                        ],
                    ],
            ],
            [
                'name'    => 'shop/category',
                'title'   => '分类管理',
                'icon'    => 'fa fa-sitemap',
                'ismenu'  => 1,
                'weigh'   => 45,
                'sublist' =>
                    [
                        [
                            'name'  => 'shop/category/index',
                            'title' => '查看',
                        ],
                        [
                            'name'  => 'shop/category/add',
                            'title' => '添加',
                        ],
                        [
                            'name'  => 'shop/category/edit',
                            'title' => '编辑',
                        ],
                        [
                            'name'  => 'shop/category/del',
                            'title' => '删除',
                        ],
                        [
                            'name'  => 'shop/category/multi',
                            'title' => '批量更新',
                        ],
                    ],
            ],
            [
                'name'    => 'shop/address',
                'title'   => '收货地址',
                'icon'    => 'fa fa-map-signs',
                'ismenu'  => 1,
                'weigh'   => 44,
                'sublist' =>
                    [
                        [
                            'name'  => 'shop/address/index',
                            'title' => '查看',
                        ],
                        [
                            'name'  => 'shop/address/add',
                            'title' => '添加',
                        ],
                        [
                            'name'  => 'shop/address/edit',
                            'title' => '编辑',
                        ],
                        [
                            'name'  => 'shop/address/del',
                            'title' => '删除',
                        ],
                        [
                            'name'  => 'shop/address/multi',
                            'title' => '批量更新',
                        ],

                        ["name" => "shop/address/recyclebin", "title" => "回收站"],
                        ["name" => "shop/address/restore", "title" => "还原"],
                        ["name" => "shop/address/destroy", "title" => "真实删除"],
                    ],
            ],
            [
                'name'    => 'shop/order_aftersales',
                'title'   => '售后管理',
                'icon'    => 'fa fa-buysellads',
                'ismenu'  => 1,
                'weigh'   => 41,
                'sublist' =>
                    [

                        [
                            'name'  => 'shop/order_aftersales/index',
                            'title' => '查看',
                        ],

                        [
                            'name'  => 'shop/order_aftersales/add',
                            'title' => '添加',
                        ],

                        [
                            'name'  => 'shop/order_aftersales/edit',
                            'title' => '编辑',
                        ],

                        [
                            'name'  => 'shop/order_aftersales/del',
                            'title' => '删除',
                        ],

                        [
                            'name'  => 'shop/order_aftersales/multi',
                            'title' => '批量更新',
                        ],
                    ],
            ],
            [
                'name'    => 'shop/exchange',
                'title'   => '积分兑换',
                'icon'    => 'fa fa-pinterest',
                'ismenu'  => 1,
                'weigh'   => 45,
                'sublist' => [
                    [
                        'name'  => 'shop/exchange/index',
                        'title' => '查看',
                    ],
                    [
                        'name'  => 'shop/exchange/add',
                        'title' => '添加',
                    ],
                    [
                        'name'  => 'shop/exchange/edit',
                        'title' => '编辑',
                    ],
                    [
                        'name'  => 'shop/exchange/del',
                        'title' => '删除',
                    ],
                    [
                        'name'  => 'shop/exchange/multi',
                        'title' => '批量更新',
                    ],
                    [
                        'name'  => 'shop/exchange/creategoods',
                        'title' => '生成商品',
                    ],
                ],
            ],
            [
                'name'    => 'shop/exchange_order',
                'title'   => '兑换订单',
                'icon'    => 'fa fa-pinterest',
                'ismenu'  => 0,
                'weigh'   => 45,
                'sublist' => [
                    [
                        'name'  => 'shop/exchange_order/index',
                        'title' => '查看',
                    ],
                    [
                        'name'  => 'shop/exchange_order/add',
                        'title' => '添加',
                    ],
                    [
                        'name'  => 'shop/exchange_order/edit',
                        'title' => '编辑',
                    ],
                    [
                        'name'  => 'shop/exchange_order/del',
                        'title' => '删除',
                    ],
                    [
                        'name'  => 'shop/exchange_order/multi',
                        'title' => '批量更新',
                    ],
                ],
            ],

            [
                'name'    => 'shop/coupon',
                'title'   => '优惠券管理',
                'icon'    => 'fa fa-jpy',
                'ismenu'  => 1,
                'weigh'   => 45,
                'sublist' => [
                    [
                        'name'  => 'shop/coupon/index',
                        'title' => '查看',
                    ],
                    [
                        'name'  => 'shop/coupon/add',
                        'title' => '添加',
                    ],
                    [
                        'name'  => 'shop/coupon/edit',
                        'title' => '编辑',
                    ],
                    [
                        'name'  => 'shop/coupon/del',
                        'title' => '删除',
                    ],
                    [
                        'name'  => 'shop/coupon/multi',
                        'title' => '批量更新',
                    ],
                ],
            ],
            [
                'name'    => 'shop/user_coupon',
                'title'   => '优惠券领取记录',
                'icon'    => 'fa fa-jpy',
                'ismenu'  => 0,
                'weigh'   => 45,
                'sublist' => [
                    [
                        'name'  => 'shop/user_coupon/index',
                        'title' => '查看',
                    ],
                    [
                        'name'  => 'shop/user_coupon/add',
                        'title' => '添加',
                    ],
                    [
                        'name'  => 'shop/user_coupon/edit',
                        'title' => '编辑',
                    ],
                    [
                        'name'  => 'shop/user_coupon/del',
                        'title' => '删除',
                    ],
                    [
                        'name'  => 'shop/user_coupon/multi',
                        'title' => '批量更新',
                    ],
                ],
            ],
            [
                'name'    => 'shop/coupon_condition',
                'title'   => '优惠券条件',
                'icon'    => 'fa fa-jpy',
                'ismenu'  => 0,
                'weigh'   => 45,
                'sublist' => [
                    [
                        'name'  => 'shop/coupon_condition/index',
                        'title' => '查看',
                    ],
                    [
                        'name'  => 'shop/coupon_condition/add',
                        'title' => '添加',
                    ],
                    [
                        'name'  => 'shop/coupon_condition/edit',
                        'title' => '编辑',
                    ],
                    [
                        'name'  => 'shop/coupon_condition/del',
                        'title' => '删除',
                    ],
                    [
                        'name'  => 'shop/coupon_condition/multi',
                        'title' => '批量更新',
                    ],
                ],
            ],
            [
                'name'    => 'shop/navigation',
                'title'   => '导航配置',
                'icon'    => 'fa fa-th',
                'ismenu'  => 1,
                'weigh'   => 38,
                'sublist' =>
                [
                    [
                        'name'  => 'shop/navigation/index',
                        'title' => '查看',
                    ],
                    [
                        'name'  => 'shop/navigation/add',
                        'title' => '添加',
                    ],
                    [
                        'name'  => 'shop/navigation/edit',
                        'title' => '编辑',
                    ],
                    [
                        'name'  => 'shop/navigation/del',
                        'title' => '删除',
                    ],
                    [
                        'name'  => 'shop/navigation/multi',
                        'title' => '批量更新',
                    ],
                ],
            ],
            [
                'name'    => 'shop/menu',
                'title'   => '菜单管理',
                'icon'    => 'fa fa-navicon',
                'ismenu'  => 1,
                'weigh'   => 36,
                'sublist' =>
                    [
                        [
                            'name'  => 'shop/menu/index',
                            'title' => '查看',
                        ],
                        [
                            'name'  => 'shop/menu/add',
                            'title' => '添加',
                        ],
                        [
                            'name'  => 'shop/menu/edit',
                            'title' => '编辑',
                        ],
                        [
                            'name'  => 'shop/menu/del',
                            'title' => '删除',
                        ],
                        [
                            'name'  => 'shop/menu/multi',
                            'title' => '批量更新',
                        ],
                    ],
            ],
            [
                'name'    => 'shop/theme',
                'title'   => '移动端预览',
                'icon'    => 'fa fa-mobile',
                'ismenu'  => 1,
                'weigh'   => 32,
                'sublist' =>
                [
                    [
                        'name'  => 'shop/theme/index',
                        'title' => '查看',
                    ],
                ],
            ],
            [
                'name'    => 'shop/config',
                'title'   => '配置管理',
                'icon'    => 'fa fa-cog',
                'ismenu'  => 1,
                'weigh'   => 55,
                'sublist' =>
                    [
                        [
                            'name'  => 'shop/config/index',
                            'title' => '查看',
                        ],
                    ],
            ],
            [
                'name'    => 'shop/report',
                'title'   => '统计控制台',
                'icon'    => 'fa fa-line-chart',
                'ismenu'  => 1,
                'weigh'   => 56,
                'sublist' =>
                    [
                        [
                            'name'  => 'shop/report/index',
                            'title' => '查看',
                        ],
                        [
                            'name'  => 'shop/report/areas',
                            'title' => '地区明细',
                        ]
                    ],
            ],
            [
                'name'    => 'shop/block',
                'title'   => '区块管理',
                'icon'    => 'fa fa-th-large',
                'weigh'   => '16',
                'sublist' => [
                    ['name' => 'shop/block/index', 'title' => '查看'],
                    ['name' => 'shop/block/add', 'title' => '添加'],
                    ['name' => 'shop/block/edit', 'title' => '修改'],
                    ['name' => 'shop/block/del', 'title' => '删除'],
                    ['name' => 'shop/block/multi', 'title' => '批量更新'],
                ],
                'remark'  => '用于管理站点的自定义区块内容,常用于广告、JS脚本、焦点图、片段代码等'
            ],
            [
                'name'    => 'shop/page',
                'title'   => '单页管理',
                'icon'    => 'fa fa-file',
                'weigh'   => '15',
                'sublist' => [
                    ['name' => 'shop/page/index', 'title' => '查看'],
                    ['name' => 'shop/page/add', 'title' => '添加'],
                    ['name' => 'shop/page/edit', 'title' => '修改'],
                    ['name' => 'shop/page/del', 'title' => '删除'],
                    ['name' => 'shop/page/multi', 'title' => '批量更新'],
                    ["name" => "shop/page/recyclebin", "title" => "回收站"],
                    ["name" => "shop/page/restore", "title" => "还原"],
                    ["name" => "shop/page/destroy", "title" => "真实删除"],
                ],
                'remark'  => '用于管理网站的单页面，可任意创建修改删除单页面'
            ],
            [
                'name'    => 'shop/search_log',
                'title'   => '搜索记录管理',
                'icon'    => 'fa fa-history',
                'weigh'   => '15',
                'sublist' => [
                    ['name' => 'shop/search_log/index', 'title' => '查看'],
                    ['name' => 'shop/search_log/add', 'title' => '添加'],
                    ['name' => 'shop/search_log/edit', 'title' => '修改'],
                    ['name' => 'shop/search_log/del', 'title' => '删除'],
                    ['name' => 'shop/search_log/multi', 'title' => '批量更新'],
                ],
                'remark'  => '用于管理网站的搜索记录日志'
            ],
            [
                'name'    => 'shop/template_msg',
                'title'   => '模板消息',
                'icon'    => 'fa fa-comment',
                'weigh'   => '15',
                'sublist' => [
                    ['name' => 'shop/template_msg/index', 'title' => '查看'],
                    ['name' => 'shop/template_msg/add', 'title' => '添加'],
                    ['name' => 'shop/template_msg/edit', 'title' => '修改'],
                    ['name' => 'shop/template_msg/del', 'title' => '删除'],
                    ['name' => 'shop/template_msg/multi', 'title' => '批量更新'],
                ],
                'remark'  => '用于发送消息通知用户'
            ],
            [
                'name'    => 'shop/attribute',
                'title'   => '商品属性',
                'ismenu'  => 0,
                'icon'    => 'fa fa-comment',
                'weigh'   => '15',
                'sublist' => [
                    ['name' => 'shop/attribute/index', 'title' => '查看'],
                    ['name' => 'shop/attribute/add', 'title' => '添加'],
                    ['name' => 'shop/attribute/edit', 'title' => '修改'],
                    ['name' => 'shop/attribute/del', 'title' => '删除'],
                    ['name' => 'shop/attribute/multi', 'title' => '批量更新'],
                ],
                'remark'  => ''
            ],
            [
                'name'    => 'shop/attribute_value',
                'title'   => '商品属性值',
                'ismenu'  => 0,
                'icon'    => 'fa fa-comment',
                'weigh'   => '15',
                'sublist' => [
                    ['name' => 'shop/attribute_value/index', 'title' => '查看'],
                    ['name' => 'shop/attribute_value/add', 'title' => '添加'],
                    ['name' => 'shop/attribute_value/edit', 'title' => '修改'],
                    ['name' => 'shop/attribute_value/del', 'title' => '删除'],
                    ['name' => 'shop/attribute_value/multi', 'title' => '批量更新'],
                ],
                'remark'  => ''
            ],
            [
                'name'    => 'shop/brand',
                'title'   => '品牌管理',
                'icon'    => 'fa fa-circle-o',
                'ismenu'  => 0,
                'weigh'   => '15',
                'sublist' => [
                    ['name' => 'shop/brand/index', 'title' => '查看'],
                    ['name' => 'shop/brand/add', 'title' => '添加'],
                    ['name' => 'shop/brand/edit', 'title' => '修改'],
                    ['name' => 'shop/brand/del', 'title' => '删除'],
                    ['name' => 'shop/brand/multi', 'title' => '批量更新'],
                ],
            ],
            [
                'name'    => 'shop/area',
                'title'   => '地区管理',
                'icon'    => 'fa fa-map-marker',
                'ismenu'  => 1,
                'weigh'   => '14',
                'sublist' => [
                    ['name' => 'shop/area/index', 'title' => '查看'],
                    ['name' => 'shop/area/add', 'title' => '添加'],
                    ['name' => 'shop/area/edit', 'title' => '修改'],
                    ['name' => 'shop/area/del', 'title' => '删除'],
                    ['name' => 'shop/area/multi', 'title' => '批量更新'],
                    ['name' => 'shop/area/import', 'title' => '导入'],
                    ['name' => 'shop/area/refresh', 'title' => '刷新'],
                ],
            ],
            [
                'name'    => 'shop/card',
                'title'   => '卡片模板',
                'icon'    => 'fa fa-file-photo-o',
                'ismenu'  => 1,
                'weigh'   => '15',
                'sublist' => [
                    ['name' => 'shop/card/index', 'title' => '查看'],
                    ['name' => 'shop/card/add', 'title' => '添加'],
                    ['name' => 'shop/card/edit', 'title' => '修改'],
                    ['name' => 'shop/card/del', 'title' => '删除'],
                    ['name' => 'shop/card/multi', 'title' => '批量更新'],
                ],
            ],
        ]
    ]
];
return $menu;
