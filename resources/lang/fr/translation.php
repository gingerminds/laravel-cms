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

    'blocks' => [
        'action' => [
            'add' => 'Ajouter un bloc',
            'edit' => 'Modifier ce bloc',
            'remove' => 'Supprimer ce bloc',
            'copy_structure' => 'Copier la structure',
            'copy' => 'Copier',
            'add_repeater_row' => 'Ajouter une ligne',
            'remove_repeater_row' => 'Supprimer cette ligne',
            'reorder_repeater_row' => 'Glisser pour réordonner',
        ],
        'message' => [
            'no_block' => 'Aucun type de bloc disponible.',
            'unknown_type' => 'Bloc de type inconnu (:type) — il a peut-être été retiré depuis.',
            'empty_preview' => 'Bloc vide, cliquez sur modifier pour le remplir.',
            'empty_canvas' => 'Ajoutez votre premier bloc',
            'confirm_remove' => 'Voulez-vous vraiment supprimer ce bloc ?',
            'loading' => 'Chargement…',
            'load_error' => 'Erreur de chargement du formulaire.',
            'validate_error' => 'Erreur de validation, réessayez.',
            'copy_structure_prompt' => 'Choisissez la langue depuis laquelle copier la structure :',
            'copy_structure_confirm' => 'Voulez-vous dupliquer cette structure ? Cela va supprimer votre structure actuelle.',
            'copy_structure_empty_source' => 'Cette langue n\'a pas encore de blocs.',
            'no_other_language' => 'Aucune autre langue disponible.',
            'repeater_row_label' => 'Élément',
        ],
        'title_text' => [
            'label' => 'Titre + Texte',
            'fields' => [
                'title' => 'Titre',
                'text' => 'Texte',
            ],
        ],
        'text_image' => [
            'label' => 'Texte + Image',
            'fields' => [
                'title' => 'Titre',
                'text' => 'Texte',
                'image' => 'Image',
                'image_position' => 'Position de l\'image (Gauche/Droite)',
                'image_position_helper' => 'Désactivé : image à gauche — Activé : image à droite',
            ],
        ],
        'video' => [
            'label' => 'Video',
            'fields' => [
                'title' => 'Titre',
                'embed_code' => 'Code Embed',
            ],
        ],
        'media' => [
            'label' => 'Média (seul)',
            'fields' => [
                'title' => 'Titre',
                'file' => 'Fichier',
            ],
        ],
        'cards' => [
            'label' => 'Cartes',
            'fields' => [
                'title' => 'Titre',
                'text' => 'Texte',
                'cards' => 'Cartes',
                'add_card' => 'Ajouter une carte',
                'card_item_label' => 'Carte',
                'card_title' => 'Titre',
                'card_description' => 'Description',
                'card_image' => 'Image',
            ],
        ],
        'slider' => [
            'label' => 'Slider',
            'fields' => [
                'title' => 'Titre',
                'slides' => 'Images',
                'add_slide' => 'Ajouter une image',
                'slide_item_label' => 'Image',
                'slide_image' => 'Image',
            ],
        ],
        'link_list' => [
            'label' => 'Liste de liens',
            'fields' => [
                'title' => 'Titre',
                'links' => 'Liens',
                'add_link' => 'Ajouter un lien',
                'link_item_label' => 'Lien',
                'link_label' => 'Libellé',
                'link_url' => 'URL',
                'link_image' => 'Image (2 Mo max)',
            ],
        ],
        'faq' => [
            'label' => 'FAQ',
            'fields' => [
                'title' => 'Titre',
                'question' => 'Question',
                'answer' => 'Réponse',
                'items' => 'Questions/Réponses',
                'add_item' => 'Ajouter une question/réponse',
                'item_label' => 'Question/Réponse',
            ],
        ],
        'media_list' => [
            'label' => 'Liste de médias',
            'fields' => [
                'title' => 'Titre',
                'items' => 'Médias',
                'add_item' => 'Ajouter un média',
                'item_label' => 'Média',
                'media' => 'Média',
                'subtitle' => 'Sous-titre',
            ],
        ]
    ],
];