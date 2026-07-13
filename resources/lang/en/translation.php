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

    'blocks' => [
        'action' => [
            'add' => 'Add a block',
            'edit' => 'Edit this block',
            'remove' => 'Remove this block',
            'copy_structure' => 'Copy structure',
            'copy' => 'Copy',
        ],
        'message' => [
            'no_block' => 'No block type available.',
            'unknown_type' => 'Unknown block type (:type) — it may have been removed since.',
            'empty_preview' => 'Empty block, click edit to fill it in.',
            'empty_canvas' => 'Add your first block',
            'confirm_remove' => 'Are you sure you want to remove this block?',
            'loading' => 'Loading…',
            'load_error' => 'Error loading the form.',
            'validate_error' => 'Validation error, please try again.',
            'copy_structure_prompt' => 'Choose the language to copy the structure from:',
            'copy_structure_confirm' => 'Do you want to duplicate this structure? This will delete your current structure.',
            'copy_structure_empty_source' => 'This language has no blocks yet.',
            'no_other_language' => 'No other language available.',
        ],
        'title_text' => [
            'label' => 'Title + Text',
            'fields' => [
                'title' => 'Title',
                'text' => 'Text',
            ],
        ],
        'text_image' => [
            'label' => 'Text + Image',
            'fields' => [
                'title' => 'Title',
                'text' => 'Text',
                'image' => 'Image',
                'image_position' => 'Image position (Left/Right)',
                'image_position_helper' => 'Off: image on the left — On: image on the right',
            ],
        ],
        'video' => [
            'label' => 'Video',
            'fields' => [
                'title' => 'Title',
                'embed_code' => 'Code Embed',
            ],
        ],
        'media' => [
            'label' => 'Media (standalone)',
            'fields' => [
                'title' => 'Title',
                'file' => 'File',
            ],
        ],
    ],
];