<?php

namespace App\Modules\ContentGeneration\Services;

class DatingContentComposer
{
    protected static array $titleTemplates = [
        "Looking for Someone Special to Share Beautiful Moments in {city}",
        "Single Woman Seeking Genuine Friendship and Real Connection in {city}",
        "Let's Get to Know Each Other and See Where Life Takes Us",
        "Hoping to Meet Someone Honest, Kind and Down to Earth near {city}",
        "Looking for Meaningful Conversations and Real Chemistry",
        "Open to New Connections and Wonderful Experiences in {city}",
        "Searching for Someone Who Values Honesty, Trust and Good Laughs",
        "Warm-Hearted Woman Looking for a Genuine Gentleman in {city}",
        "Ready for Meaningful Dates, Great Coffee, and Real Talks in {city}",
        "Down-to-Earth Soul Looking for True Connection and Companionship",
        "Hoping to Find a Real Spark with Someone Sincere in {state}",
        "Sweet, Thoughtful, and Ready to Meet Someone Wonderful in {city}",
        "Looking for That Rare Chemistry and a Sincere Partnership",
        "Kind, Creative Woman Seeking Mutual Respect and Romance near {city}",
        "Looking for Laughter, Real Dates, and a Genuine Partner",
        "Ready to Share Life's Simple Joys and Great Adventures in {city}",
        "Seeking a Gentleman Who Appreciates Honesty, Warmth and Good Vibes",
        "Hoping to Connect with Someone Who Values Quality Time in {city}",
        "Spontaneous, Warm, and Hoping to Meet My Special Someone in {state}",
        "Let's Skip the Games and Build Something Meaningful Together in {city}",
    ];

    protected static array $hobbySets = [
        "weekend farmers markets, discovering quiet local coffee shops, coastal drives, and trying new home recipes",
        "outdoor hiking trails, visiting indie bookstores, catching live acoustic music, and relaxing Sunday brunches",
        "art exhibitions, exploring hidden local eateries, photography walks, and cozy movie nights with tea",
        "evening sunset jogs, spontaneous weekend road trips, landscape photography, and learning classic cooking",
        "botanical gardens, reading thought-provoking books, attending local cultural festivals, and yoga mornings",
        "vintage thrift hunting, making artisan pastries, beach walks, and deep conversations under starry skies"
    ];

    protected static array $personalityTraits = [
        "warm, optimistic, and someone who genuinely values empathy, good manners, and real communication",
        "down-to-earth, cheerful, and someone who always appreciates authenticity and a good sense of humor",
        "kind-hearted, driven, and passionate about personal growth and creating memories with good people",
        "easygoing, thoughtful, and creative, with a sincere appreciation for life's simple, meaningful moments"
    ];

    protected static array $partnerDesires = [
        "a thoughtful, emotionally mature gentleman who knows how to communicate with respect, honesty, and kindness",
        "someone genuine and grounded who values honest connection, laughs easily, and is ready for real dating",
        "a good-hearted, dependable partner who appreciates quality time, mutual encouragement, and shared adventures",
        "someone sincere and down to earth who values loyalty, great conversations, and true romantic chemistry"
    ];

    protected static array $callToActions = [
        "If you're sincere, grounded, and looking for genuine dating in {city}, I'd love to hear from you. Send me a warm note introducing yourself and your favorite local spot!",
        "If this resonates with what you're seeking in {city}, please feel free to reach out. Tell me a little about yourself and what always brings a smile to your face.",
        "Life is always richer when shared with someone special. If you value honest communication and real dates here in {city}, send me a message so we can chat!",
        "If you're ready to meet someone honest and explore meaningful connection in {state}, drop me a friendly message. Let's see where a good conversation leads us!"
    ];

    /**
     * Compose a humanized, policy-compliant dating title and description.
     */
    public static function compose(string $name, int $age, string $cityName, string $stateName, string $stateCode): array
    {
        $titleTemplate = self::$titleTemplates[array_rand(self::$titleTemplates)];
        $title = str_replace(
            ['{city}', '{state}'],
            [$cityName, $stateName],
            $titleTemplate
        );

        $hobbies = self::$hobbySets[array_rand(self::$hobbySets)];
        $trait = self::$personalityTraits[array_rand(self::$personalityTraits)];
        $desire = self::$partnerDesires[array_rand(self::$partnerDesires)];
        $ctaTemplate = self::$callToActions[array_rand(self::$callToActions)];
        $cta = str_replace(['{city}', '{state}'], [$cityName, $stateName], $ctaTemplate);

        $intros = [
            "Hello there! My name is {$name}, and I'm a {$age}-year-old woman living right here in {$cityName}, {$stateCode}.",
            "Hi! I'm {$name}, {$age}, currently calling {$cityName}, {$stateName} my home.",
            "Hey! Warm greetings from {$cityName}. I'm {$name}, {$age} years young, living and enjoying life in {$stateName}."
        ];
        $intro = $intros[array_rand($intros)];

        $description = "{$intro} "
            . "By day I keep myself focused and productive, but outside of work, I truly enjoy {$hobbies}. "
            . "Friends and loved ones often describe me as {$trait}. "
            . "I believe that the best relationships are built on trust, laughter, and genuine mutual respect.\n\n"
            . "I'm looking to meet {$desire}. "
            . "Whether that means meeting for an artisan coffee in {$cityName}, enjoying an evening dinner date, or discovering peaceful scenic spots around {$stateName}, I'm hoping to build something real and lasting. "
            . "I'm not interested in games or endless superficial texting—just honest connection, good vibes, and seeing where life takes two people who click.\n\n"
            . "{$cta}";

        return [
            'title' => $title,
            'description' => $description,
            'hobbies' => $hobbies,
        ];
    }
}

