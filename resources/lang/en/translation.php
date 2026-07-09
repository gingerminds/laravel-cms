<?php

return [
    'form' => [
        'url' => 'URL',
        'is_target_blank' => 'Open in new tab',
        'is_no_referrer' => 'No referrer',
        'is_no_opener' => 'No opener',
        'is_no_follow' => 'No follow',
        'main_visual' => 'Main Visual',
        'title' => 'Title',
        'slug' => 'Slug',
        'hook' => 'Hook',
        'published_at' => 'Published At',
        'archived_at' => 'Archived At',
        'status' => 'Status'
    ],

    'menus' => [
        'name_s' => 'Menu',
        'name_p' => 'Menus',
        'manage' => 'Manage Menus',
    ],

    'menu_items' => [
        'name_s' => 'Menu Item',
        'name_p' => 'Menu Items',
        'manage' => 'Manage Menu Items',
        'form' => [
            'parent_id' => 'Parent',
        ],
        'action' => [
            'add_child' => 'Add a child',
        ],
        'message' => [
            'no_result' => 'No items in this menu',
        ],
    ],

    'pages' => [
        'name_s' => 'Page',
        'name_p' => 'Pages',
        'manage' => 'Manage Pages',
        'form' => [
            'status' => 'Status',
            'category' => 'Category',
        ],
        'statuses' => [
            'draft' => 'Draft',
            'published' => 'Published',
            'archived' => 'Archived',
        ],
        'message' => [
            'category_required' => 'Choose a category before creating a page.',
            'is_unique_taken' => 'This category only accepts one page, and it is already used by another page.',
            'url_taken' => 'Another page already resolves to this exact URL for this language.',
        ],
    ],

    'page_categories' => [
        'name_s' => 'Page category',
        'name_p' => 'Page categories',
        'manage' => 'Manage Page Categories',
        'form' => [
            'parent_id' => 'Parent',
            'name' => 'Name',
            'prefix' => 'URL prefix',
            'prefix_hint' => 'Leave blank to not add a URL segment for pages in this category.',
            'is_unique' => 'Unique category (only one page allowed)',
        ],
        'action' => [
            'add_child' => 'Add a child',
            'choose' => 'Choose a category',
        ],
        'message' => [
            'no_result' => 'No categories yet',
        ],
    ],
];