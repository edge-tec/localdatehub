<?php

namespace Database\Seeders;

use App\Modules\Categories\Models\Category;
use App\Modules\Categories\Models\CategoryAttribute;
use App\Modules\Categories\Models\CategoryAttributeOption;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Men Seeking Women',
                'slug' => 'men-seeking-women',
                'code' => 'M4W',
                'description' => 'Local adult men looking to meet women for dating, romance, and personal connections.',
                'icon' => 'user-heart',
                'display_order' => 1,
                'is_featured' => true,
            ],
            [
                'name' => 'Women Seeking Men',
                'slug' => 'women-seeking-men',
                'code' => 'W4M',
                'description' => 'Local adult women looking to meet men for dating, companionship, and romantic connections.',
                'icon' => 'user-heart',
                'display_order' => 2,
                'is_featured' => true,
            ],
            [
                'name' => 'Men Seeking Men',
                'slug' => 'men-seeking-men',
                'code' => 'M4M',
                'description' => 'Adult men seeking men for dating, friendship, and personal connections.',
                'icon' => 'users',
                'display_order' => 3,
                'is_featured' => true,
            ],
            [
                'name' => 'Women Seeking Women',
                'slug' => 'women-seeking-women',
                'code' => 'W4W',
                'description' => 'Adult women seeking women for dating, friendship, and romance.',
                'icon' => 'users',
                'display_order' => 4,
                'is_featured' => true,
            ],
            [
                'name' => 'Activity Partners',
                'slug' => 'activity-partners',
                'code' => 'ACT',
                'description' => 'Find a partner for concerts, dining, fitness, hiking, sports, or travel.',
                'icon' => 'calendar',
                'display_order' => 5,
                'is_featured' => true,
            ],
            [
                'name' => 'Long-Term Dating',
                'slug' => 'long-term-dating',
                'code' => 'LTR',
                'description' => 'Intentional connections for singles seeking committed, serious relationships.',
                'icon' => 'sparkles',
                'display_order' => 6,
                'is_featured' => true,
            ],
            [
                'name' => 'Casual Dating',
                'slug' => 'casual-dating',
                'code' => 'CSL',
                'description' => 'Low-pressure local dates, coffee meets, and social mixers for adults.',
                'icon' => 'coffee',
                'display_order' => 7,
                'is_featured' => false,
            ],
            [
                'name' => 'LGBTQ+ Personal Connections',
                'slug' => 'lgbtq-connections',
                'code' => 'LGBTQ',
                'description' => 'Welcoming community connections for non-binary, trans, and queer singles.',
                'icon' => 'rainbow',
                'display_order' => 8,
                'is_featured' => false,
            ],
            [
                'name' => 'Couples Seeking Women',
                'slug' => 'couples-seeking-women',
                'code' => 'C4W',
                'description' => 'Consensual adult connections for couples seeking women.',
                'icon' => 'heart-handshake',
                'display_order' => 9,
                'is_featured' => false,
            ],
            [
                'name' => 'Couples Seeking Men',
                'slug' => 'couples-seeking-men',
                'code' => 'C4M',
                'description' => 'Consensual adult connections for couples seeking men.',
                'icon' => 'heart-handshake',
                'display_order' => 10,
                'is_featured' => false,
            ],
            [
                'name' => 'Couples Seeking Couples',
                'slug' => 'couples-seeking-couples',
                'code' => 'C4C',
                'description' => 'Social and dating connections between adult couples.',
                'icon' => 'heart-handshake',
                'display_order' => 11,
                'is_featured' => false,
            ],
            [
                'name' => 'Friendship',
                'slug' => 'friendship',
                'code' => 'FRN',
                'description' => 'Platonic connections, new-in-town buddies, and hobby friends.',
                'icon' => 'smile',
                'display_order' => 12,
                'is_featured' => false,
            ],
        ];

        foreach ($categories as $catData) {
            $cat = Category::firstOrCreate(
                ['slug' => $catData['slug']],
                $catData
            );

            // Add standard dynamic attributes for personal categories
            $attrIntent = CategoryAttribute::firstOrCreate(
                ['category_id' => $cat->id, 'code' => 'intent'],
                [
                    'name' => 'Relationship Intent',
                    'field_type' => 'select',
                    'is_required' => true,
                    'is_searchable' => true,
                    'is_filterable' => true,
                    'display_order' => 1,
                ]
            );

            foreach (['Dating', 'Long-term Relationship', 'Casual Meets', 'Activity Companion', 'Friendship'] as $idx => $opt) {
                CategoryAttributeOption::firstOrCreate(
                    ['category_attribute_id' => $attrIntent->id, 'value' => strtolower(str_replace(' ', '_', $opt))],
                    ['label' => $opt, 'display_order' => $idx + 1]
                );
            }

            $attrStatus = CategoryAttribute::firstOrCreate(
                ['category_id' => $cat->id, 'code' => 'relationship_status'],
                [
                    'name' => 'Current Status',
                    'field_type' => 'select',
                    'is_required' => false,
                    'is_searchable' => true,
                    'is_filterable' => true,
                    'display_order' => 2,
                ]
            );

            foreach (['Single', 'Divorced', 'Widowed', 'Separated', 'Never Married'] as $idx => $opt) {
                CategoryAttributeOption::firstOrCreate(
                    ['category_attribute_id' => $attrStatus->id, 'value' => strtolower($opt)],
                    ['label' => $opt, 'display_order' => $idx + 1]
                );
            }
        }
    }
}
