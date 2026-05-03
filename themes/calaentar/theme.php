<?php

use App\Admin\Actions\ResetRadiusAction;

return [
    'name' => 'Calaentar',
    'author' => 'Paymenter',
    'url' => 'https://paymenter.org',

    'settings' => [
        // Functional toggles
        [
            'name' => 'direct_checkout',
            'label' => 'Direct Checkout',
            'type' => 'checkbox',
            'default' => false,
            'database_type' => 'boolean',
            'description' => 'Don\'t show the product overview page, go directly to the checkout page',
        ],
        [
            'name' => 'small_images',
            'label' => 'Small Images',
            'type' => 'checkbox',
            'default' => false,
            'database_type' => 'boolean',
            'description' => 'Show small images in the product overview page',
        ],
        [
            'name' => 'show_category_description',
            'label' => 'Show Category Description',
            'type' => 'checkbox',
            'default' => true,
            'database_type' => 'boolean',
            'description' => 'Show the category description in the product overview page',
        ],
        [
            'name' => 'logo_display',
            'label' => 'Logo display',
            'type' => 'select',
            'options' => [
                'none' => 'None',
                'text' => 'Text only',
                'logo' => 'Logo only',
                'logo-and-name' => 'Logo and Text',
            ],
            'default' => 'logo-and-name',
            'description' => 'What to show in the navigation: nothing, just the site name, just the logo image, or both.',
        ],

        // Brand colors (dark-only theme — no light variants)
        [
            'name' => 'primary',
            'label' => 'Primary - Magenta',
            'type' => 'color',
            'default' => 'hsl(320, 90%, 60%)',
        ],
        [
            'name' => 'secondary',
            'label' => 'Secondary - Purple',
            'type' => 'color',
            'default' => 'hsl(270, 70%, 55%)',
        ],
        [
            'name' => 'accent',
            'label' => 'Accent - Highlight',
            'type' => 'color',
            'default' => 'hsl(290, 95%, 70%)',
        ],
        [
            'name' => 'neutral',
            'label' => 'Neutral - Borders & Accents',
            'type' => 'color',
            'default' => 'hsl(260, 20%, 22%)',
        ],
        [
            'name' => 'base',
            'label' => 'Base - Text Color',
            'type' => 'color',
            'default' => 'hsl(260, 20%, 96%)',
        ],
        [
            'name' => 'muted',
            'label' => 'Muted - Secondary Text',
            'type' => 'color',
            'default' => 'hsl(260, 15%, 65%)',
        ],
        [
            'name' => 'inverted',
            'label' => 'Inverted - Text on Light Surfaces',
            'type' => 'color',
            'default' => 'hsl(260, 30%, 10%)',
        ],
        [
            'name' => 'background',
            'label' => 'Background - Page',
            'type' => 'color',
            'default' => 'hsl(260, 35%, 6%)',
        ],
        [
            'name' => 'background-secondary',
            'label' => 'Background - Cards & Surfaces',
            'type' => 'color',
            'default' => 'hsl(260, 30%, 10%)',
        ],

        // Ambient gradient stops + accent glow
        [
            'name' => 'gradient-from',
            'label' => 'Gradient Stop - From',
            'type' => 'color',
            'default' => 'hsl(320, 80%, 35%)',
        ],
        [
            'name' => 'gradient-via',
            'label' => 'Gradient Stop - Via',
            'type' => 'color',
            'default' => 'hsl(280, 70%, 25%)',
        ],
        [
            'name' => 'gradient-to',
            'label' => 'Gradient Stop - To',
            'type' => 'color',
            'default' => 'hsl(260, 35%, 6%)',
        ],
        [
            'name' => 'gradient-angle',
            'label' => 'Gradient Angle (degrees)',
            'type' => 'number',
            'default' => '135',
            'description' => 'Angle of the linear gradient overlay (0-360)',
        ],
        [
            'name' => 'glow-color',
            'label' => 'Glow Color',
            'type' => 'color',
            'default' => 'hsl(320, 90%, 60%)',
        ],

        // Border-radius scale (CSS values, e.g. "0.5rem", "12px", "9999px")
        [
            'name' => 'radius-sm',
            'label' => 'Border Radius - Small',
            'type' => 'text',
            'default' => '0.5rem',
            'action' => ResetRadiusAction::class,
        ],
        [
            'name' => 'radius-md',
            'label' => 'Border Radius - Medium',
            'type' => 'text',
            'default' => '0.75rem',
        ],
        [
            'name' => 'radius-lg',
            'label' => 'Border Radius - Large',
            'type' => 'text',
            'default' => '1rem',
        ],
        [
            'name' => 'radius-xl',
            'label' => 'Border Radius - Extra Large',
            'type' => 'text',
            'default' => '1.5rem',
        ],

        // Glass-morphism effect
        [
            'name' => 'glass-blur',
            'label' => 'Glass Blur',
            'type' => 'text',
            'default' => '14px',
            'description' => 'Backdrop blur amount (e.g. "12px")',
        ],
        [
            'name' => 'glass-opacity',
            'label' => 'Glass Opacity (0-1)',
            'type' => 'text',
            'default' => '0.5',
            'description' => 'Surface opacity for glass cards/panels',
        ],
        [
            'name' => 'glass-border-opacity',
            'label' => 'Glass Border Opacity (0-1)',
            'type' => 'text',
            'default' => '0.12',
            'description' => 'Opacity of the soft border on glass surfaces',
        ],
    ],
];
