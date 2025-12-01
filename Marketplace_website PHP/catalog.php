<?php
/**
 * Central catalogue seeding + helpers for demo listings.
 * Everything lives in the session so we can simulate APIs locally.
 */

if (!function_exists('catalog_all')) {
    /**
     * Return all listings for a given type (housing, cars, freelance).
     */
    function catalog_all(string $type): array
    {
        catalog_ensure_seed($type);
        return array_values($_SESSION['catalog'][$type] ?? []);
    }

    /**
     * Find a single listing by type + ID.
     */
    function catalog_find(string $type, int $id): ?array
    {
        catalog_ensure_seed($type);
        return $_SESSION['catalog'][$type][$id] ?? null;
    }

    /**
     * Update base price for a listing (used by lightweight admin tools).
     */
    function catalog_update_price(string $type, int $id, float $price): bool
    {
        catalog_ensure_seed($type);
        if (!isset($_SESSION['catalog'][$type][$id])) {
            return false;
        }
        $_SESSION['catalog'][$type][$id]['price'] = round($price, 2);
        return true;
    }

    /**
     * Ensure demo data exists for the requested type.
     */
    function catalog_ensure_seed(string $type): void
    {
        if (!isset($_SESSION['catalog'])) {
            $_SESSION['catalog'] = [];
        }
        if (isset($_SESSION['catalog'][$type])) {
            return;
        }

        switch ($type) {
            case 'housing':
                catalog_seed_housing();
                break;
            case 'car':
            case 'cars':
                catalog_seed_cars();
                break;
            case 'freelance':
                catalog_seed_freelance();
                break;
            default:
                $_SESSION['catalog'][$type] = [];
        }
    }

    /**
     * Helper: generate availability map for the next N days.
     */
    function catalog_generate_calendar(int $days = 21): array
    {
        $output = [];
        $today = new DateTimeImmutable('today', new DateTimeZone('Africa/Lusaka'));
        for ($i = 0; $i < $days; $i++) {
            $date = $today->modify("+$i day");
            $rand = ($i % 7 === 0) ? 5 : rand(0, 100);
            $status = $rand < 10 ? 'blocked' : ($rand < 35 ? 'booked' : 'open');
            $output[] = [
                'date' => $date->format('Y-m-d'),
                'label' => $date->format('D, M j'),
                'status' => $status,
            ];
        }
        return $output;
    }

    function catalog_fake_phone(int $seed): string
    {
        $prefixes = ['097', '096', '095'];
        $prefix = $prefixes[$seed % count($prefixes)];
        return $prefix . str_pad((string)($seed * 73 % 1000000), 7, '4', STR_PAD_LEFT);
    }

    function catalog_seed_housing(): void
    {
        $samples = [
            [
                'title' => 'Roma Garden Loft',
                'province' => 'Lusaka',
                'price' => 850,
                'image' => 'https://images.unsplash.com/photo-1505691938895-1758d7feb511?q=80&w=1600&auto=format&fit=crop',
                'description' => 'Solar-backed loft with chef-on-call, perfect for dual housing + training cohorts.',
                'amenities' => ['Solar Backup', 'WiFi', 'Chef on Call', 'Laundry', 'Security'],
                'tags' => ['Mobile Money Ready', 'Apprentice Housing'],
                'address' => 'Roma, Lusaka',
                'seller' => 'Chanda Homes'
            ],
            [
                'title' => 'Copperbelt Trade Dorms',
                'province' => 'Copperbelt',
                'price' => 620,
                'image' => 'https://images.unsplash.com/photo-1505692971220-ae4241c9265b?q=80&w=1600&auto=format&fit=crop',
                'description' => 'Ten-bed dorms with communal workshop access and ZRA-ready invoicing.',
                'amenities' => ['Workshop Access', 'WiFi', '24/7 Security', 'Kitchen'],
                'tags' => ['Trade Dorms', 'ZRA Receipt'],
                'address' => 'Ndola, Copperbelt',
                'seller' => 'Mwamba Estates'
            ],
            [
                'title' => 'Victoria Falls Co-Living',
                'province' => 'Southern',
                'price' => 980,
                'image' => 'https://images.unsplash.com/photo-1505691723518-36a5ac3be353?q=80&w=1600&auto=format&fit=crop',
                'description' => 'Premium view suites bundled with tourism-hospitality apprenticeships.',
                'amenities' => ['Spa', 'River View', 'Airport Shuttle', 'Breakfast'],
                'tags' => ['Tourism Track', 'Mobile Money Ready'],
                'address' => 'Livingstone, Southern',
                'seller' => 'Mukuni Stays'
            ],
            [
                'title' => 'Chongwe Solar Farm Pods',
                'province' => 'Central',
                'price' => 540,
                'image' => 'https://images.unsplash.com/photo-1493809842364-78817add7ffb?q=80&w=1600&auto=format&fit=crop',
                'description' => 'Eco pods ideal for agri-tech fellows needing long-stay packages.',
                'amenities' => ['Solar Farm', 'Water Harvesting', 'WiFi', 'Study Desks'],
                'tags' => ['Agri-Tech', 'Eco Pods'],
                'address' => 'Chongwe, Central',
                'seller' => 'EcoZed Ventures'
            ],
            [
                'title' => 'Kitwe Makers Residence',
                'province' => 'Copperbelt',
                'price' => 760,
                'image' => 'https://images.unsplash.com/photo-1505693416384-2c1b6dfb53e0?q=80&w=1600&auto=format&fit=crop',
                'description' => 'Workshop-friendly residence with diesel backup and maker lab.',
                'amenities' => ['Maker Lab', 'Fiber Internet', 'Gym', 'Backup Power'],
                'tags' => ['Maker Badge', 'ISV Verified'],
                'address' => 'Kitwe, Copperbelt',
                'seller' => 'Copper Forge'
            ],
            [
                'title' => 'Lake Bangweulu Retreat',
                'province' => 'Luapula',
                'price' => 690,
                'image' => 'https://images.unsplash.com/photo-1505691938895-1758d7feb511?q=80&w=1600&auto=format&fit=crop',
                'description' => 'Waterfront chalets supporting fisheries and aquaculture learners.',
                'amenities' => ['Boat Dock', 'Solar Freezers', 'Chef', 'WiFi'],
                'tags' => ['Aquaculture Track', 'Mobile Money Ready'],
                'address' => 'Samfya, Luapula',
                'seller' => 'Lakefront Africa'
            ],
        ];

        $_SESSION['catalog']['housing'] = [];
        $id = 1;
        foreach ($samples as $sample) {
            $_SESSION['catalog']['housing'][$id] = array_merge($sample, [
                'id' => $id,
                'type' => 'housing',
                'currency' => 'ZMW',
                'price_unit' => 'night',
                'rating' => round(4.5 + (rand(0, 40) / 100), 1),
                'reviews' => rand(35, 480),
                'gallery' => [$sample['image']],
                'calendar' => catalog_generate_calendar(),
                'contact_phone' => catalog_fake_phone($id),
                'max_guests' => rand(4, 12),
            ]);
            $id++;
        }
    }

    function catalog_seed_cars(): void
    {
        $samples = [
            [
                'title' => 'Toyota Hilux 4x4',
                'province' => 'Lusaka',
                'price' => 950,
                'image' => 'https://images.unsplash.com/photo-1502877338535-766e1452684a?q=80&w=1600&auto=format&fit=crop',
                'description' => 'Rugged double cab perfect for mine visits and bush deliveries.',
                'amenities' => ['4x4', 'Tracker', 'Driver Optional'],
                'seller' => 'Fleet Hub Zambia'
            ],
            [
                'title' => 'Isuzu NPR Reefer',
                'province' => 'Copperbelt',
                'price' => 1400,
                'image' => 'https://images.unsplash.com/photo-1469474968028-56623f02e42e?q=80&w=1600&auto=format&fit=crop',
                'description' => 'Cold-chain truck with ZABS-compliant monitoring.',
                'amenities' => ['Cold Chain', '24/7 Support', 'Driver Included'],
                'seller' => 'Chilanga Logistics'
            ],
            [
                'title' => 'Toyota Hiace Shuttle',
                'province' => 'Southern',
                'price' => 780,
                'image' => 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?q=80&w=1600&auto=format&fit=crop',
                'description' => '14-seater shuttle for tourism and corporate transfers.',
                'amenities' => ['Tour Guide Network', 'AC', 'Insurance'],
                'seller' => 'Livingstone Rides'
            ],
            [
                'title' => 'Nissan Leaf EV Pilot',
                'province' => 'Lusaka',
                'price' => 520,
                'image' => 'https://images.unsplash.com/photo-1489515217757-5fd1be406fef?q=80&w=1600&auto=format&fit=crop',
                'description' => 'Electric vehicle pilot for green-delivery startups.',
                'amenities' => ['Chargers Included', 'Telematics', 'Insurance'],
                'seller' => 'E-Mobility Zambia'
            ],
            [
                'title' => 'Ford Ranger XL',
                'province' => 'Northern',
                'price' => 890,
                'image' => 'https://images.unsplash.com/photo-1503736334956-4c8f8e92946d?q=80&w=1600&auto=format&fit=crop',
                'description' => 'Reliable pick-up for agri extension teams.',
                'amenities' => ['Canopy', 'GPS', 'Rural Assist'],
                'seller' => 'Kasama Motors'
            ],
        ];

        $_SESSION['catalog']['car'] = [];
        $id = 1;
        foreach ($samples as $sample) {
            $_SESSION['catalog']['car'][$id] = array_merge($sample, [
                'id' => $id,
                'type' => 'car',
                'currency' => 'ZMW',
                'price_unit' => 'day',
                'rating' => round(4.4 + (rand(0, 35) / 100), 1),
                'reviews' => rand(18, 260),
                'gallery' => [$sample['image']],
                'calendar' => catalog_generate_calendar(),
                'contact_phone' => catalog_fake_phone($id + 40),
                'tags' => ['Mobile Money Ready', 'ZRA Invoice'],
            ]);
            $id++;
        }
    }

    function catalog_seed_freelance(): void
    {
        $samples = [
            [
                'title' => 'Responsive Zambian SME Website',
                'seller' => 'Alex D.',
                'rating' => 4.9,
                'reviews' => 120,
                'price' => 5500,
                'category' => 'Programming',
                'image' => 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?q=80&w=1600&auto=format&fit=crop',
                'description' => 'Full-stack websites with local payment rails + ZRA invoicing.',
                'badges' => ['Mobile Money', 'Napsa Friendly']
            ],
            [
                'title' => 'Logo + Brand Pack for Cooperatives',
                'seller' => 'Mia S.',
                'rating' => 5.0,
                'reviews' => 340,
                'price' => 4200,
                'category' => 'Design',
                'image' => 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?q=80&w=1600&auto=format&fit=crop',
                'description' => 'Farm-to-market visual systems with bilingual style guides.',
                'badges' => ['ISV Verified']
            ],
            [
                'title' => 'SEO Blog Pack for Agri Hubs',
                'seller' => 'Chris P.',
                'rating' => 4.8,
                'reviews' => 210,
                'price' => 1450,
                'category' => 'Writing',
                'image' => 'https://images.unsplash.com/photo-1516979187457-637abb4f9353?q=80&w=1600&auto=format&fit=crop',
                'description' => 'Localized copy that ranks for Zambian procurement keywords.',
                'badges' => ['Mobile Money']
            ],
            [
                'title' => 'Video Stories for Skills Training',
                'seller' => 'Jane F.',
                'rating' => 4.7,
                'reviews' => 95,
                'price' => 6200,
                'category' => 'Video',
                'image' => 'https://images.unsplash.com/photo-1515378791036-0648a3ef77b2?q=80&w=1600&auto=format&fit=crop',
                'description' => 'Documentary reels for artisan academies and co-ops.',
                'badges' => ['Drone Pilot']
            ],
            [
                'title' => 'Chitenge Inspired UI Design',
                'seller' => 'Ivy R.',
                'rating' => 4.9,
                'reviews' => 52,
                'price' => 3800,
                'category' => 'Design',
                'image' => 'https://images.unsplash.com/photo-1517430816045-df4b7de11d1d?q=80&w=1600&auto=format&fit=crop',
                'description' => 'Design systems rooted in Zambian textiles + accessibility.',
                'badges' => ['Inclusive Design']
            ],
            [
                'title' => 'AI WhatsApp Bot for Airtel & MTN',
                'seller' => 'Noel K.',
                'rating' => 4.95,
                'reviews' => 68,
                'price' => 7800,
                'category' => 'AI',
                'image' => 'https://images.unsplash.com/photo-1555255707-83b2c03c5f6b?q=80&w=1500&auto=format&fit=crop',
                'description' => 'Customer support bots wired to local telcos + Hubtel APIs.',
                'badges' => ['Fintech Ready']
            ],
        ];

        $_SESSION['catalog']['freelance'] = [];
        $id = 1;
        foreach ($samples as $sample) {
            $_SESSION['catalog']['freelance'][$id] = array_merge($sample, [
                'id' => $id,
                'type' => 'freelance',
                'currency' => 'ZMW',
                'price_unit' => 'project',
                'gallery' => [$sample['image']],
                'calendar' => catalog_generate_calendar(),
                'contact_phone' => catalog_fake_phone($id + 80),
                'tags' => array_merge(['Remote Friendly'], $sample['badges'] ?? []),
            ]);
            $id++;
        }
    }
}

