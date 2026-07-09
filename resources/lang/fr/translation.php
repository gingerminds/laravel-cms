<?php

return [
    'form' => [
        'url' => 'URL',
        'is_target_blank' => 'Ouvrir dans un nouvel onglet',
        'is_no_referrer' => 'No referrer',
        'is_no_opener' => 'No opener',
        'is_no_follow' => 'No follow',
        'main_visual' => 'Visuel principal',
        'title' => 'Titre',
        'slug' => 'Slug',
        'hook' => 'Accroche',
        'published_at' => 'Publié le',
        'archived_at' => 'Archivé le',
        'status' => 'Statut'
    ],

    'menus' => [
        'name_s' => 'Menu',
        'name_p' => 'Menus',
        'manage' => 'Gestion des menus',
    ],

    'menu_items' => [
        'name_s' => 'Objet du menu',
        'name_p' => 'Objets du menu',
        'manage' => 'Gestion des objets du menu',
        'form' => [
            'parent_id' => 'Parent',
        ],
        'action' => [
            'add_child' => 'Ajouter un enfant',
        ],
        'message' => [
            'no_result' => 'Aucun élément dans ce menu',
        ],
    ],

    'pages' => [
        'name_s' => 'Page',
        'name_p' => 'Pages',
        'manage' => 'Gestion des pages',
        'form' => [
            'status' => 'Statut',
            'category' => 'Catégorie',
        ],
        'statuses' => [
            'draft' => 'Brouillon',
            'published' => 'Publié',
            'archived' => 'Archivé',
        ],
        'message' => [
            'category_required' => 'Choisissez une catégorie avant de créer une page.',
            'is_unique_taken' => 'Cette catégorie n\'accepte qu\'une seule page, et elle est déjà utilisée par une autre page.',
            'url_taken' => 'Une autre page correspond déjà exactement à cette URL pour cette langue.',
        ],
    ],

    'page_categories' => [
        'name_s' => 'Catégorie',
        'name_p' => 'Catégories',
        'manage' => 'Gestion des catégories de pages',
        'form' => [
            'parent_id' => 'Parent',
            'name' => 'Nom',
            'prefix' => 'Préfixe d\'URL',
            'prefix_hint' => 'Laisser vide pour ne pas ajouter de segment à l\'URL des pages de cette catégorie.',
            'is_unique' => 'Catégorie unique (une seule page autorisée)',
        ],
        'action' => [
            'add_child' => 'Ajouter un enfant',
            'choose' => 'Choisir une catégorie',
        ],
        'message' => [
            'no_result' => 'Aucune catégorie pour le moment',
        ],
    ],
];