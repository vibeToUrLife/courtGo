<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Bookable sports & activities
    |--------------------------------------------------------------------------
    |
    | The full curated list owners can choose from (modelled on
    | courtsite.my's category page), grouped only by comment. Owners pick
    | "Other" for anything not here. Customers filter by this same list.
    |
    */

    'sports' => [
        // Racquet
        'Pickleball', 'Badminton', 'Padel', 'Squash', 'Tennis', 'Table Tennis', 'Skyball',
        // Team
        'Futsal', 'Football', 'Light Volleyball', 'Volleyball', '3x3 Basketball', 'Basketball',
        'Netball', 'Field Hockey', 'Dodgeball', 'Lawn Bowl', 'Frisbee', 'Cricket', 'Captain Ball',
        'Handball', 'Indoor Hockey', 'Sepak Takraw', 'Teqball', 'Flag Football', 'Rugby',
        // Water
        'Free Diving', 'Mermaiding', 'Scuba Diving', 'Swimming',
        // Recreational
        'Archery Tag', 'Paintball', 'Zorb Attack', 'Bowling', 'Foosball', 'Golf Driving Range',
        'Go-Kart', 'Martial Arts', 'Pool Table',
        // Fitness & spaces
        'Dance Studio', 'Fitness Space', 'Gym', 'Running Track', 'Wall Climbing',
        'Event Space', 'Sporty Celebration', 'Event Room', 'Chalet',
        // Classes
        'Boxing', 'Brazilian Jiu-Jitsu', 'Capoeira', 'Fitness', "Fighter's Strength And Conditioning",
        'Grappling', 'Kickboxing', 'MMA', 'Muay Thai', 'Muay Thai Fitness', 'Taekwondo',
    ],

    /*
    |--------------------------------------------------------------------------
    | Popular sports
    |--------------------------------------------------------------------------
    |
    | A short subset shown as quick tiles on the homepage (the full list is
    | searchable in the filters).
    |
    */

    'popular_sports' => [
        'Badminton', 'Futsal', 'Football', 'Tennis', 'Pickleball',
        'Padel', 'Basketball', 'Volleyball', 'Squash', 'Table Tennis',
    ],

    /*
    |--------------------------------------------------------------------------
    | Sport icons
    |--------------------------------------------------------------------------
    |
    | Each sport's tile icon is a hand-drawn SVG in the <x-sport-icon> Blade
    | component (resources/views/components/sport-icon.blade.php), keyed by the
    | sport name above. Add a matching @case there when you add a sport here.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Slot lengths
    |--------------------------------------------------------------------------
    |
    | How long each bookable slot can be, in hours (string keys avoid PHP's
    | float-key casting). Shown as a dropdown when owners build a schedule.
    |
    */

    'slot_lengths' => [
        '0.5' => '30 minutes',
        '1' => '1 hour',
        '1.5' => '1.5 hours',
        '2' => '2 hours',
        '3' => '3 hours',
        '4' => '4 hours',
    ],

    /*
    |--------------------------------------------------------------------------
    | Malaysian states & federal territories
    |--------------------------------------------------------------------------
    */

    'states' => [
        'Johor',
        'Kedah',
        'Kelantan',
        'Melaka',
        'Negeri Sembilan',
        'Pahang',
        'Penang',
        'Perak',
        'Perlis',
        'Sabah',
        'Sarawak',
        'Selangor',
        'Terengganu',
        'Kuala Lumpur',
        'Labuan',
        'Putrajaya',
    ],

    /*
    |--------------------------------------------------------------------------
    | Support contact
    |--------------------------------------------------------------------------
    |
    | Shown to owners who need a sport that isn't in the curated list.
    |
    */

    'support_email' => env('COURTGO_SUPPORT_EMAIL', 'support@courtgo.my'),

];
