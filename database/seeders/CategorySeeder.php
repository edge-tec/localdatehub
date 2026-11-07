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
        // 1. Initial Top-Level Root: Personals
        $root = Category::updateOrCreate(
            ['slug' => 'personals'],
            [
                'parent_id' => null,
                'depth' => 0,
                'name' => 'Personals',
                'code' => 'PERS',
                'description' => 'Lawful USA adult personal classifieds and dating-related personal connections. Anti-triangulation, verified 18+ members only.',
                'safety_guidelines' => 'Safety First: Meet in public places, never send money, and verify who you are talking to. Prostitution and commercial sex work are strictly prohibited.',
                'icon' => 'heart-handshake',
                'display_order' => 1,
                'is_active' => true,
                'is_featured' => true,
                'allowed_contact_methods' => ['platform_message', 'email_relay', 'phone'],
                'seo_metadata' => [
                    'meta_title' => 'USA Adult Personal Classifieds & Dating Connections | LocalAdZone',
                    'meta_description' => 'Browse verified 18+ adult personal classifieds across all 50 US states and major metropolitan areas.',
                    'keywords' => ['personals', 'dating', 'adult classifieds', 'local meetups', 'connections'],
                ],
            ]
        );

        // 2. Initial Category Groups (Children of Personals, depth 1)
        $groups = [
            [
                'name' => 'Men Seeking',
                'slug' => 'men-seeking',
                'code' => 'M_SEEK',
                'description' => 'Local adult men seeking personal dating connections, companionship, and romantic encounters.',
                'icon' => 'user',
                'display_order' => 1,
                'is_featured' => true,
            ],
            [
                'name' => 'Women Seeking',
                'slug' => 'women-seeking',
                'code' => 'W_SEEK',
                'description' => 'Local adult women seeking dating, companionship, activity partners, and romantic connections.',
                'icon' => 'user-heart',
                'display_order' => 2,
                'is_featured' => true,
            ],
            [
                'name' => 'Couples Seeking',
                'slug' => 'couples-seeking',
                'code' => 'C_SEEK',
                'description' => 'Consensual adult dating and social connections for couples looking to meet singles or other couples.',
                'icon' => 'users',
                'display_order' => 3,
                'is_featured' => false,
            ],
            [
                'name' => 'LGBTQ+ Personal Connections',
                'slug' => 'lgbtq-connections',
                'code' => 'LGBTQ',
                'description' => 'Welcoming community connections for non-binary, trans, queer, and same-gender dating.',
                'icon' => 'rainbow',
                'display_order' => 4,
                'is_featured' => true,
            ],
            [
                'name' => 'General Personal Connections',
                'slug' => 'general-connections',
                'code' => 'GEN_CONN',
                'description' => 'Platonic friendships, activity companions, coffee dates, and serious intentional dating.',
                'icon' => 'sparkles',
                'display_order' => 5,
                'is_featured' => true,
            ],
        ];

        $groupModels = [];
        foreach ($groups as $groupData) {
            $groupData['parent_id'] = $root->id;
            $groupData['depth'] = 1;
            $groupData['safety_guidelines'] = 'Always verify consent, age, and identity. Respect privacy boundaries and never share financial credentials.';
            $groupData['allowed_contact_methods'] = ['platform_message', 'email_relay', 'phone'];
            $groupData['seo_metadata'] = [
                'meta_title' => "{$groupData['name']} Classifieds & Personals | LocalAdZone",
                'meta_description' => "Find local adult {$groupData['name']} listings and verified personal connections.",
            ];

            $groupModels[$groupData['slug']] = Category::updateOrCreate(
                ['slug' => $groupData['slug']],
                $groupData
            );
        }

        // 3. Configurable Subcategories (Children of Groups, depth 2)
        $subcategories = [
            // Under Men Seeking
            [
                'group' => 'men-seeking',
                'name' => 'Men Seeking Women',
                'slug' => 'men-seeking-women',
                'code' => 'M4W',
                'description' => 'Local adult men looking to meet women for dating, romance, and personal connections.',
                'icon' => 'user-heart',
                'display_order' => 1,
                'is_featured' => true,
            ],
            [
                'group' => 'men-seeking',
                'name' => 'Men Seeking Men',
                'slug' => 'men-seeking-men',
                'code' => 'M4M',
                'description' => 'Adult men seeking men for dating, friendship, and personal connections.',
                'icon' => 'users',
                'display_order' => 2,
                'is_featured' => true,
            ],
            [
                'group' => 'men-seeking',
                'name' => 'Men Seeking Couples',
                'slug' => 'men-seeking-couples',
                'code' => 'M4C',
                'description' => 'Adult men seeking consensual social and dating connections with couples.',
                'icon' => 'heart-handshake',
                'display_order' => 3,
                'is_featured' => false,
            ],

            // Under Women Seeking
            [
                'group' => 'women-seeking',
                'name' => 'Women Seeking Men',
                'slug' => 'women-seeking-men',
                'code' => 'W4M',
                'description' => 'Local adult women looking to meet men for dating, companionship, and romantic connections.',
                'icon' => 'user-heart',
                'display_order' => 1,
                'is_featured' => true,
            ],
            [
                'group' => 'women-seeking',
                'name' => 'Women Seeking Women',
                'slug' => 'women-seeking-women',
                'code' => 'W4W',
                'description' => 'Adult women seeking women for dating, friendship, and romance.',
                'icon' => 'users',
                'display_order' => 2,
                'is_featured' => true,
            ],
            [
                'group' => 'women-seeking',
                'name' => 'Women Seeking Couples',
                'slug' => 'women-seeking-couples',
                'code' => 'W4C',
                'description' => 'Adult women seeking consensual connections with couples.',
                'icon' => 'heart-handshake',
                'display_order' => 3,
                'is_featured' => false,
            ],

            // Under Couples Seeking
            [
                'group' => 'couples-seeking',
                'name' => 'Couples Seeking Women',
                'slug' => 'couples-seeking-women',
                'code' => 'C4W',
                'description' => 'Consensual adult connections for couples seeking women.',
                'icon' => 'heart-handshake',
                'display_order' => 1,
                'is_featured' => false,
            ],
            [
                'group' => 'couples-seeking',
                'name' => 'Couples Seeking Men',
                'slug' => 'couples-seeking-men',
                'code' => 'C4M',
                'description' => 'Consensual adult connections for couples seeking men.',
                'icon' => 'heart-handshake',
                'display_order' => 2,
                'is_featured' => false,
            ],
            [
                'group' => 'couples-seeking',
                'name' => 'Couples Seeking Couples',
                'slug' => 'couples-seeking-couples',
                'code' => 'C4C',
                'description' => 'Social and dating connections between adult couples.',
                'icon' => 'heart-handshake',
                'display_order' => 3,
                'is_featured' => false,
            ],

            // Under LGBTQ+ Connections
            [
                'group' => 'lgbtq-connections',
                'name' => 'Non-Binary & Trans Connections',
                'slug' => 'trans-nonbinary-connections',
                'code' => 'TGNC',
                'description' => 'Affirming connections for non-binary, trans, and gender non-conforming singles.',
                'icon' => 'rainbow',
                'display_order' => 1,
                'is_featured' => false,
            ],
            [
                'group' => 'lgbtq-connections',
                'name' => 'Queer Singles & Dating',
                'slug' => 'queer-singles',
                'code' => 'QUEER',
                'description' => 'Queer singles looking for romance, dating, and community.',
                'icon' => 'sparkles',
                'display_order' => 2,
                'is_featured' => false,
            ],

            // Under General Personal Connections
            [
                'group' => 'general-connections',
                'name' => 'Activity Partners',
                'slug' => 'activity-partners',
                'code' => 'ACT',
                'description' => 'Find a partner for concerts, dining, fitness, hiking, sports, or travel.',
                'icon' => 'calendar',
                'display_order' => 1,
                'is_featured' => true,
            ],
            [
                'group' => 'general-connections',
                'name' => 'Long-Term Dating',
                'slug' => 'long-term-dating',
                'code' => 'LTR',
                'description' => 'Intentional connections for singles seeking committed, serious relationships.',
                'icon' => 'sparkles',
                'display_order' => 2,
                'is_featured' => true,
            ],
            [
                'group' => 'general-connections',
                'name' => 'Casual Dating',
                'slug' => 'casual-dating',
                'code' => 'CSL',
                'description' => 'Low-pressure local dates, coffee meets, and social mixers for adults.',
                'icon' => 'coffee',
                'display_order' => 3,
                'is_featured' => false,
            ],
            [
                'group' => 'general-connections',
                'name' => 'Friendship',
                'slug' => 'friendship',
                'code' => 'FRN',
                'description' => 'Platonic connections, new-in-town buddies, and hobby friends.',
                'icon' => 'smile',
                'display_order' => 4,
                'is_featured' => false,
            ],
        ];

        $subcatModels = [];
        foreach ($subcategories as $subData) {
            $groupSlug = $subData['group'];
            unset($subData['group']);

            $parentGroup = $groupModels[$groupSlug];
            $subData['parent_id'] = $parentGroup->id;
            $subData['depth'] = 2;
            $subData['is_active'] = true;
            $subData['allowed_contact_methods'] = ['platform_message', 'email_relay', 'phone'];
            $subData['safety_guidelines'] = 'All members must be 18+. Do not solicit commercial sexual services. Report suspicious accounts immediately.';
            $subData['seo_metadata'] = [
                'meta_title' => "Local {$subData['name']} Classifieds & Personals | LocalAdZone",
                'meta_description' => "Meet local adult singles in {$subData['name']}. Safe, verified dating classifieds with privacy protection.",
                'keywords' => [strtolower($subData['code']), strtolower($subData['name']), 'personals', 'dating', 'singles'],
            ];

            $subcatModels[$subData['slug']] = Category::updateOrCreate(
                ['slug' => $subData['slug']],
                $subData
            );
        }

        // 4. Global & Subcategory Dynamic Attributes
        // Global Attribute
        $attrIntentGlobal = CategoryAttribute::updateOrCreate(
            ['category_id' => null, 'code' => 'intent'],
            [
                'name' => 'Relationship Intent',
                'placeholder' => 'Select your dating intent',
                'help_text' => 'What type of personal connection are you seeking?',
                'field_type' => 'select',
                'is_required' => true,
                'is_searchable' => true,
                'is_filterable' => true,
                'display_order' => 1,
            ]
        );

        $intentOptions = [
            'dating' => 'Dating & Romance',
            'long_term' => 'Long-Term Relationship',
            'casual' => 'Casual Coffee & Meets',
            'activity' => 'Activity Companion',
            'friendship' => 'Platonic Friendship',
        ];

        $idx = 1;
        foreach ($intentOptions as $val => $label) {
            CategoryAttributeOption::updateOrCreate(
                ['category_attribute_id' => $attrIntentGlobal->id, 'value' => $val],
                ['label' => $label, 'display_order' => $idx++]
            );
        }

        // Also assign intent directly to leaf personal subcategories for backwards compatibility with direct attributes relationship
        foreach ($subcatModels as $cat) {
            $catAttrIntent = CategoryAttribute::updateOrCreate(
                ['category_id' => $cat->id, 'code' => 'intent'],
                [
                    'name' => 'Relationship Intent',
                    'placeholder' => 'Select your dating intent',
                    'help_text' => 'What type of personal connection are you seeking?',
                    'field_type' => 'select',
                    'is_required' => true,
                    'is_searchable' => true,
                    'is_filterable' => true,
                    'display_order' => 1,
                ]
            );

            $optIdx = 1;
            foreach ($intentOptions as $val => $label) {
                CategoryAttributeOption::updateOrCreate(
                    ['category_attribute_id' => $catAttrIntent->id, 'value' => $val],
                    ['label' => $label, 'display_order' => $optIdx++]
                );
            }
        }

        $attrStatus = CategoryAttribute::updateOrCreate(
            ['category_id' => null, 'code' => 'relationship_status'],
            [
                'name' => 'Current Relationship Status',
                'placeholder' => 'Select current status',
                'help_text' => 'Your current relationship status.',
                'field_type' => 'select',
                'is_required' => false,
                'is_searchable' => true,
                'is_filterable' => true,
                'display_order' => 2,
            ]
        );

        $statusOptions = [
            'single' => 'Single',
            'divorced' => 'Divorced',
            'separated' => 'Separated',
            'widowed' => 'Widowed',
            'never_married' => 'Never Married',
            'in_relationship' => 'In a Relationship',
        ];

        $idx = 1;
        foreach ($statusOptions as $val => $label) {
            CategoryAttributeOption::updateOrCreate(
                ['category_attribute_id' => $attrStatus->id, 'value' => $val],
                ['label' => $label, 'display_order' => $idx++]
            );
        }

        // 5. Category-Specific Dynamic Attributes
        // For Men Seeking and Women Seeking (e.g. M4W, W4M, M4M, W4W)
        foreach (['men-seeking-women', 'women-seeking-men', 'men-seeking-men', 'women-seeking-women'] as $catSlug) {
            $cat = $subcatModels[$catSlug];

            $attrHeight = CategoryAttribute::updateOrCreate(
                ['category_id' => $cat->id, 'code' => 'height_inches'],
                [
                    'name' => 'Height (Inches)',
                    'placeholder' => 'e.g. 70 for 5\'10"',
                    'help_text' => 'Your approximate height in total inches.',
                    'unit' => 'inches',
                    'field_type' => 'number',
                    'is_required' => false,
                    'is_searchable' => true,
                    'is_filterable' => true,
                    'display_order' => 10,
                    'validation_rules' => ['numeric', 'min:48', 'max:96'],
                ]
            );

            $attrSmoking = CategoryAttribute::updateOrCreate(
                ['category_id' => $cat->id, 'code' => 'smoking'],
                [
                    'name' => 'Smoking Habits',
                    'placeholder' => 'Select smoking frequency',
                    'help_text' => 'Do you smoke tobacco or vape?',
                    'field_type' => 'select',
                    'is_required' => false,
                    'is_searchable' => true,
                    'is_filterable' => true,
                    'display_order' => 11,
                ]
            );

            $idx = 1;
            foreach (['non_smoker' => 'Non-smoker', 'occasional' => 'Occasional / Social', 'regular' => 'Regular Smoker'] as $v => $l) {
                CategoryAttributeOption::updateOrCreate(
                    ['category_attribute_id' => $attrSmoking->id, 'value' => $v],
                    ['label' => $l, 'display_order' => $idx++]
                );
            }

            $attrInterests = CategoryAttribute::updateOrCreate(
                ['category_id' => $cat->id, 'code' => 'interests'],
                [
                    'name' => 'Hobbies & Interests',
                    'placeholder' => 'e.g. Live music, hiking, indie films, foodie, cycling',
                    'help_text' => 'Mention activities and passions you enjoy.',
                    'field_type' => 'text',
                    'is_required' => false,
                    'is_searchable' => true,
                    'is_filterable' => false,
                    'display_order' => 12,
                ]
            );
        }

        // For Activity Partners
        if (isset($subcatModels['activity-partners'])) {
            $actCat = $subcatModels['activity-partners'];

            $attrActType = CategoryAttribute::updateOrCreate(
                ['category_id' => $actCat->id, 'code' => 'activity_type'],
                [
                    'name' => 'Preferred Activity',
                    'placeholder' => 'Select activity type',
                    'help_text' => 'What kind of event or outing would you like to share?',
                    'field_type' => 'select',
                    'is_required' => true,
                    'is_searchable' => true,
                    'is_filterable' => true,
                    'display_order' => 10,
                ]
            );

            $idx = 1;
            foreach ([
                'dining' => 'Dining & Foodie Adventures',
                'concerts' => 'Live Music & Concerts',
                'outdoors' => 'Hiking & Outdoor Recreation',
                'fitness' => 'Fitness & Gym Partner',
                'travel' => 'Travel & Weekend Getaways',
                'arts' => 'Museums, Theatre & Arts',
                'board_games' => 'Trivia & Board Games',
            ] as $v => $l) {
                CategoryAttributeOption::updateOrCreate(
                    ['category_attribute_id' => $attrActType->id, 'value' => $v],
                    ['label' => $l, 'display_order' => $idx++]
                );
            }

            $attrSchedule = CategoryAttribute::updateOrCreate(
                ['category_id' => $actCat->id, 'code' => 'availability'],
                [
                    'name' => 'Schedule Availability',
                    'placeholder' => 'Select your availability',
                    'field_type' => 'select',
                    'is_required' => false,
                    'is_searchable' => true,
                    'is_filterable' => true,
                    'display_order' => 11,
                ]
            );

            $idx = 1;
            foreach (['weekends' => 'Weekends', 'weekday_evenings' => 'Weekday Evenings', 'flexible' => 'Flexible Schedule'] as $v => $l) {
                CategoryAttributeOption::updateOrCreate(
                    ['category_attribute_id' => $attrSchedule->id, 'value' => $v],
                    ['label' => $l, 'display_order' => $idx++]
                );
            }
        }
    }
}
