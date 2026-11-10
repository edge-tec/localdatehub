<?php

namespace App\Modules\ContentGeneration\Services;

class AmericanNameGenerator
{
    protected static array $firstNames = [
        'Emma', 'Olivia', 'Ava', 'Sophia', 'Isabella', 'Charlotte', 'Amelia', 'Mia', 'Harper', 'Evelyn',
        'Abigail', 'Emily', 'Ella', 'Elizabeth', 'Camila', 'Luna', 'Sofia', 'Avery', 'Mila', 'Aria',
        'Scarlett', 'Penelope', 'Layla', 'Chloe', 'Victoria', 'Madison', 'Eleanor', 'Grace', 'Nora', 'Riley',
        'Zoey', 'Hannah', 'Hazel', 'Lily', 'Ellie', 'Violet', 'Lillian', 'Zoe', 'Stella', 'Aurora',
        'Natalie', 'Emilia', 'Everly', 'Leah', 'Aubrey', 'Willow', 'Addison', 'Lucy', 'Audrey', 'Bella',
        'Nova', 'Brooklyn', 'Paisley', 'Claire', 'Skylar', 'Isla', 'Genesis', 'Naomi', 'Elena', 'Caroline',
        'Eliana', 'Anna', 'Maya', 'Valentina', 'Ruby', 'Kennedy', 'Ivy', 'Arianna', 'Aaliyah', 'Cora',
        'Madelyn', 'Alice', 'Kinsley', 'Hailey', 'Gabriella', 'Allison', 'Gianna', 'Serenity', 'Samantha', 'Sarah',
        'Quinn', 'Eva', 'Piper', 'Sadie', 'Delilah', 'Josephine', 'Nevaeh', 'Adeline', 'Emery', 'Lydia',
        'Clara', 'Vivian', 'Madeline', 'Peyton', 'Julia', 'Rylee', 'Brielle', 'Reagan', 'Natalia', 'Jade'
    ];

    protected static array $lastNames = [
        'Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Miller', 'Davis', 'Wilson', 'Anderson', 'Taylor',
        'Thomas', 'Moore', 'Jackson', 'Martin', 'Lee', 'Thompson', 'White', 'Harris', 'Clark', 'Lewis',
        'Robinson', 'Walker', 'Young', 'Allen', 'King', 'Wright', 'Scott', 'Hill', 'Flores', 'Green',
        'Adams', 'Nelson', 'Baker', 'Hall', 'Campbell', 'Mitchell', 'Carter', 'Roberts', 'Phillips', 'Evans',
        'Turner', 'Parker', 'Edwards', 'Collins', 'Stewart', 'Morris', 'Murphy', 'Cook', 'Rogers', 'Morgan',
        'Cooper', 'Peterson', 'Bailey', 'Reed', 'Kelly', 'Howard', 'Cox', 'Ward', 'Richardson', 'Watson',
        'Brooks', 'Wood', 'James', 'Bennett', 'Gray', 'Hughes', 'Price', 'Sanders', 'Myers', 'Long',
        'Ross', 'Foster', 'Powell', 'Sullivan', 'Russell', 'Ortiz', 'Jenkins', 'Perry', 'Butler', 'Barnes'
    ];

    protected static array $generated = [];

    /**
     * Generate a unique realistic American female full name.
     */
    public static function generateUniqueName(): array
    {
        $attempts = 0;
        do {
            $first = self::$firstNames[array_rand(self::$firstNames)];
            $last = self::$lastNames[array_rand(self::$lastNames)];
            $fullName = "{$first} {$last}";
            $attempts++;
        } while (isset(self::$generated[$fullName]) && $attempts < 200);

        self::$generated[$fullName] = true;

        $usernameBase = strtolower($first . '_' . substr($last, 0, 1) . rand(10, 99));

        return [
            'first_name' => $first,
            'last_name' => $last,
            'full_name' => $fullName,
            'username' => $usernameBase,
        ];
    }
}

