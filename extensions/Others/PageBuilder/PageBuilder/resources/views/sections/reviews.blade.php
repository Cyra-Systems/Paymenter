@php
    $data = $data ?? [];
$colorMap = [
    'primary' => 'var(--hpb-color-primary)',
    'primary-20' => 'var(--hpb-color-primary-20)',
    'primary-dark' => 'var(--hpb-color-primary-dark)',
    'secondary' => 'var(--hpb-color-secondary)',
    'secondary-20' => 'var(--hpb-color-secondary-20)',
    'background' => 'var(--hpb-color-background)',
    'text-primary' => 'var(--hpb-color-base)',
    'text-secondary' => 'var(--hpb-color-muted)',
    'base' => 'var(--hpb-color-base)',
    'muted' => 'var(--hpb-color-muted)',
    'neutral' => 'var(--hpb-color-neutral)',
    'bg-background' => 'var(--hpb-color-bg-background)',
    'color-background' => 'var(--hpb-color-color-background)',
];
@endphp

@switch($variation)
    @case('1')
        <section class="py-20 relative">
            <div class="container mx-auto max-w-[1320px] px-4">
                <div class="text-center space-y-4 mb-8 py-16">
                    <h2 class="text-3xl md:text-5xl font-bold text-gray-900 dark:text-white">{!! data_get($data, 'content.section_title', 'What Our Customers Say') !!}</h2>
                    <p class="text-xl text-gray-700 dark:text-gray-200 max-w-3xl mx-auto">{!! data_get($data, 'content.section_description', 'Real feedback from real customers about their experience with our hosting services') !!}</p>
                </div>

                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                    @php $reviews = array_slice(data_get($data, 'content.reviews', []), 0, 3); @endphp
                    @foreach ($reviews as $review)
                        <div class="border-1 rounded-3xl p-8 page-builder__card card-gradient" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow);">
                            <div class="flex items-center mb-6">
                                <div class="flex text-yellow-400">
                                    <i class="fa-solid fa-star"></i>
                                    <i class="fa-solid fa-star"></i>
                                    <i class="fa-solid fa-star"></i>
                                    <i class="fa-solid fa-star"></i>
                                    <i class="fa-solid fa-star"></i>
                                </div>
                            </div>
                            <p class="text-gray-700 dark:text-gray-200 mb-6 leading-relaxed">{!! data_get($review, 'quote', '') !!}</p>
                            <div class="flex items-center">
                                <div class="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold text-lg mr-4" style="background-color: hsl({{ $colorMap['primary'] }});">
                                    {{ data_get($review, 'initials', '') }}
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900 dark:text-white">{!! data_get($review, 'name', '') !!}</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{!! data_get($review, 'title', '') !!}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
        @break

    @case('2')
        <section class="py-20 relative">
            <div class="container mx-auto max-w-[1320px] px-4">
                <div class="text-center space-y-4 mb-8 py-16">
                    <h2 class="text-3xl md:text-5xl font-bold text-gray-900 dark:text-white">{!! data_get($data, 'content.section_title', 'Trusted by Thousands') !!}</h2>
                    <p class="text-xl text-gray-700 dark:text-gray-200 max-w-3xl mx-auto">{!! data_get($data, 'content.section_description', 'Join thousands of satisfied customers who have chosen our hosting platform') !!}</p>
                </div>

                <div class="grid md:grid-cols-2 gap-8">
                    @php $reviews = array_slice(data_get($data, 'content.reviews', []), 0, 2); @endphp
                    @foreach ($reviews as $review)
                        <div class="border-1 rounded-3xl p-8 page-builder__card card-gradient" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow);">
                            <div class="flex items-center mb-4">
                                <div class="flex text-yellow-400 mr-4">
                                    <i class="fa-solid fa-star"></i>
                                    <i class="fa-solid fa-star"></i>
                                    <i class="fa-solid fa-star"></i>
                                    <i class="fa-solid fa-star"></i>
                                    <i class="fa-solid fa-star"></i>
                                </div>
                                <span class="text-gray-700 dark:text-gray-200 font-semibold">{{ data_get($review, 'rating', '5.0') }}</span>
                            </div>
                            <p class="text-gray-800 dark:text-gray-200 mb-6 leading-relaxed text-lg">{!! data_get($review, 'quote', '') !!}</p>
                            <div class="flex items-center">
                                <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-white font-bold text-xl mr-4" style="background-color: hsl({{ $colorMap['primary'] }});">
                                    {{ data_get($review, 'initials', '') }}
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-900 dark:text-white text-lg">{!! data_get($review, 'name', '') !!}</h4>
                                    <p class="text-gray-700 dark:text-gray-200">{!! data_get($review, 'title', '') !!}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
        @break

    @case('3')
        <section class="py-20 relative">
            <div class="container mx-auto max-w-[1320px] px-4">
                <div class="text-center space-y-4 mb-8 py-16">
                    <h2 class="text-3xl md:text-5xl font-bold text-gray-900 dark:text-white">{!! data_get($data, 'content.section_title', 'Customer Success Stories') !!}</h2>
                    <p class="text-xl text-gray-700 dark:text-gray-200 max-w-3xl mx-auto">{!! data_get($data, 'content.section_description', 'Discover how our customers have transformed their businesses with our hosting solutions') !!}</p>
                </div>

                <div class="grid md:grid-cols-3 gap-8">
                    @php $reviews = array_slice(data_get($data, 'content.reviews', []), 0, 3); @endphp
                    @foreach ($reviews as $review)
                        <div class="border-1 rounded-3xl p-8 page-builder__card card-gradient" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow);">
                            <div class="flex items-center mb-6">
                                <div class="flex text-yellow-400 mr-4">
                                    <i class="fa-solid fa-star"></i>
                                    <i class="fa-solid fa-star"></i>
                                    <i class="fa-solid fa-star"></i>
                                    <i class="fa-solid fa-star"></i>
                                    <i class="fa-solid fa-star"></i>
                                </div>
                                <span class="text-gray-700 dark:text-gray-200 font-semibold">{{ data_get($review, 'rating', '5.0') }}</span>
                            </div>
                            <blockquote class="text-gray-800 dark:text-gray-200 mb-6 leading-relaxed text-lg italic">{!! data_get($review, 'quote', '') !!}</blockquote>
                            <div class="flex items-center">
                                <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-white font-bold text-xl mr-4" style="background-color: hsl({{ $colorMap['primary'] }});">
                                    {{ data_get($review, 'initials', '') }}
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-900 dark:text-white text-lg">{!! data_get($review, 'name', '') !!}</h4>
                                    <p class="text-gray-700 dark:text-gray-200">{!! data_get($review, 'title', '') !!}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
        @break

    @case('4')
        <section class="py-20 relative">
            <div class="container mx-auto max-w-[1320px] px-4">
                <div class="text-center space-y-4 mb-8 py-16">
                    <h2 class="text-3xl md:text-5xl font-bold text-gray-900 dark:text-white">{!! data_get($data, 'content.section_title', 'Client Testimonials') !!}</h2>
                    <p class="text-xl text-gray-700 dark:text-gray-200 max-w-3xl mx-auto">{!! data_get($data, 'content.section_description', 'Hear directly from our clients about their experience and success with our platform') !!}</p>
                </div>

                <div class="space-y-12">
                    <div class="flex flex-col lg:flex-row items-center gap-8 lg:gap-12">
                        @php $r0 = data_get($data, 'content.reviews.0'); @endphp
                        @if ($r0)
                            <div class="lg:w-1/2">
                                <div class="border-1 rounded-3xl p-8  page-builder__card card-gradient" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow);">
                                    <div class="flex items-center mb-6">
                                        <div class="flex text-yellow-400 mr-4">
                                            <i class="fa-solid fa-star"></i>
                                            <i class="fa-solid fa-star"></i>
                                            <i class="fa-solid fa-star"></i>
                                            <i class="fa-solid fa-star"></i>
                                            <i class="fa-solid fa-star"></i>
                                        </div>
                                        <span class="text-gray-700 dark:text-gray-200 font-semibold">{{ data_get($r0, 'rating', '5.0') }}</span>
                                    </div>
                                    <p class="text-gray-700 dark:text-gray-200 mb-6 leading-relaxed text-lg">{!! data_get($r0, 'quote', '') !!}</p>
                                    <div class="flex items-center">
                                        <div class="w-16 h-16 rounded-full flex items-center justify-center text-white font-bold text-xl mr-4" style="background-color: hsl({{ $colorMap['primary'] }});">
                                            {{ data_get($r0, 'initials', '') }}
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-gray-900 dark:text-white text-lg">{!! data_get($r0, 'name', '') !!}</h4>
                                            <p class="text-gray-700 dark:text-gray-200">{!! data_get($r0, 'title', '') !!}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                        <div class="lg:w-1/2 text-center lg:text-left">
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">{!! data_get($data, 'content.aside_1_title', 'Trusted by Industry Leaders') !!}</h3>
                            <p class="text-gray-700 dark:text-gray-200 text-lg">{!! data_get($data, 'content.aside_1_text', 'Our platform powers thousands of successful businesses worldwide, from startups to Fortune 500 companies.') !!}</p>
                        </div>
                    </div>

                    <div class="flex flex-col lg:flex-row-reverse items-center gap-8 lg:gap-12">
                        @php $r1 = data_get($data, 'content.reviews.1'); @endphp
                        @if ($r1)
                            <div class="lg:w-1/2">
                                <div class="border-1 rounded-3xl p-8  page-builder__card card-gradient" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow);">
                                    <div class="flex items-center mb-6">
                                        <div class="flex text-yellow-400 mr-4">
                                            <i class="fa-solid fa-star"></i>
                                            <i class="fa-solid fa-star"></i>
                                            <i class="fa-solid fa-star"></i>
                                            <i class="fa-solid fa-star"></i>
                                            <i class="fa-solid fa-star"></i>
                                        </div>
                                        <span class="text-gray-700 dark:text-gray-200 font-semibold">{{ data_get($r1, 'rating', '5.0') }}</span>
                                    </div>
                                    <p class="text-gray-700 dark:text-gray-200 mb-6 leading-relaxed text-lg">{!! data_get($r1, 'quote', '') !!}</p>
                                    <div class="flex items-center">
                                        <div class="w-16 h-16 rounded-full flex items-center justify-center text-white font-bold text-xl mr-4" style="background-color: hsl({{ $colorMap['primary'] }});">
                                            {{ data_get($r1, 'initials', '') }}
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-gray-900 dark:text-white text-lg">{!! data_get($r1, 'name', '') !!}</h4>
                                            <p class="text-gray-700 dark:text-gray-200">{!! data_get($r1, 'title', '') !!}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                        <div class="lg:w-1/2 text-center lg:text-right">
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">{!! data_get($data, 'content.aside_2_title', 'Enterprise-Grade Reliability') !!}</h3>
                            <p class="text-gray-700 dark:text-gray-200 text-lg">{!! data_get($data, 'content.aside_2_text', 'Built for businesses that demand the highest levels of performance, security, and support.') !!}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        @break

    @case('5')
        <section class="py-20 relative">
            <div class="container mx-auto max-w-[1320px] px-4">
                <div class="text-center space-y-4 mb-8 py-16">
                    <h2 class="text-3xl md:text-5xl font-bold text-gray-900 dark:text-white">{!! data_get($data, 'content.section_title', 'Customer Reviews') !!}</h2>
                    <p class="text-xl text-gray-700 dark:text-gray-200 max-w-3xl mx-auto">{!! data_get($data, 'content.section_description', 'See what our customers are saying about their hosting experience') !!}</p>
                </div>

                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="text-center border-1rounded-2xl p-6  page-builder__card card-gradient" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow);">
                        <div class="w-16 h-16 rounded-full flex items-center justify-center text-white text-xl mx-auto mb-4" style="background-color: hsl({{ $colorMap['primary'] }});">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <div class="flex justify-center mb-3">
                            <div class="flex text-yellow-400 text-sm">
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                            </div>
                        </div>
                        <p class="text-gray-700 dark:text-gray-200 mb-4 leading-relaxed text-sm">{!! data_get($data, 'content.reviews.0.quote', 'Fast and reliable hosting. Perfect for our startup needs.') !!}</p>
                        <h4 class="font-semibold text-gray-900 dark:text-white text-sm">{!! data_get($data, 'content.reviews.0.name', 'Mike Chen') !!}</h4>
                        <p class="text-gray-600 dark:text-gray-300 text-xs">{!! data_get($data, 'content.reviews.0.title', 'Startup Founder') !!}</p>
                    </div>

                    <div class="text-center border-1rounded-2xl p-6  page-builder__card card-gradient" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow);">
                        <div class="w-16 h-16 rounded-full flex items-center justify-center text-white text-xl mx-auto mb-4" style="background-color: hsl({{ $colorMap['primary'] }});">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <div class="flex justify-center mb-3">
                            <div class="flex text-yellow-400 text-sm">
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                            </div>
                        </div>
                        <p class="text-gray-700 dark:text-gray-200 mb-4 leading-relaxed text-sm">{!! data_get($data, 'content.reviews.1.quote', "Excellent customer support. They're always there when we need help.") !!}</p>
                        <h4 class="font-semibold text-gray-900 dark:text-white text-sm">{!! data_get($data, 'content.reviews.1.name', 'Sarah Wilson') !!}</h4>
                        <p class="text-gray-600 dark:text-gray-300 text-xs">{!! data_get($data, 'content.reviews.1.title', 'Web Developer') !!}</p>
                    </div>

                    <div class="text-center border-1rounded-2xl p-6  page-builder__card card-gradient" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow);">
                        <div class="w-16 h-16 rounded-full flex items-center justify-center text-white text-xl mx-auto mb-4" style="background-color: hsl({{ $colorMap['primary'] }});">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <div class="flex justify-center mb-3">
                            <div class="flex text-yellow-400 text-sm">
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                            </div>
                        </div>
                        <p class="text-gray-700 dark:text-gray-200 mb-4 leading-relaxed text-sm">{!! data_get($data, 'content.reviews.2.quote', 'Great uptime and performance. Highly recommended for businesses.') !!}</p>
                        <h4 class="font-semibold text-gray-900 dark:text-white text-sm">{!! data_get($data, 'content.reviews.2.name', 'David Brown') !!}</h4>
                        <p class="text-gray-600 dark:text-gray-300 text-xs">{!! data_get($data, 'content.reviews.2.title', 'Business Owner') !!}</p>
                    </div>

                    <div class="text-center border-1rounded-2xl p-6  page-builder__card card-gradient" style="border-radius: {{ (isset($colors) && is_array($colors) && array_key_exists('card_radius', $colors)) ? (int) $colors['card_radius'] : 12 }}px; box-shadow: {{ (isset($colors) && is_array($colors) && array_key_exists('card_shadow', $colors)) ? $colors['card_shadow'] : '0 4px 12px rgba(0,0,0,0.08)' }};">
                        <div class="w-16 h-16 rounded-full flex items-center justify-center text-white text-xl mx-auto mb-4" style="background-color: hsl({{ $colorMap['primary'] }});">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <div class="flex justify-center mb-3">
                            <div class="flex text-yellow-400 text-sm">
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                            </div>
                        </div>
                        <p class="text-gray-700 dark:text-gray-200 mb-4 leading-relaxed text-sm">{!! data_get($data, 'content.reviews.3.quote', "Easy to use and manage. Perfect for our team's workflow.") !!}</p>
                        <h4 class="font-semibold text-gray-900 dark:text-white text-sm">{!! data_get($data, 'content.reviews.3.name', 'Lisa Garcia') !!}</h4>
                        <p class="text-gray-600 dark:text-gray-300 text-xs">{!! data_get($data, 'content.reviews.3.title', 'Team Lead') !!}</p>
                    </div>
                </div>

                <div class="text-center mt-12">
                    <div class="inline-flex items-center space-x-8 text-gray-600 dark:text-gray-400">
                        <div class="flex items-center space-x-2">
                            <i class="fa-solid fa-star text-yellow-400"></i>
                            <span class="font-semibold">{!! data_get($data, 'content.metrics.average_rating', '4.9/5') !!}</span>
                            <span>Average Rating</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <i class="fa-solid fa-users text-gray-400"></i>
                            <span class="font-semibold">{!! data_get($data, 'content.metrics.total_reviews', '10,000+') !!}</span>
                            <span>Reviews</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <i class="fa-solid fa-thumbs-up text-gray-400"></i>
                            <span class="font-semibold">{!! data_get($data, 'content.metrics.recommend_percent', '98%') !!}</span>
                            <span>Recommend</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        @break

    @default
        <section class="py-20 relative">
            <div class="container mx-auto max-w-[1320px] px-4 text-center py-16">
                <h2 class="text-3xl md:text-5xl font-bold text-gray-900 dark:text-white mb-8">Reviews Section</h2>
                <p class="text-xl text-gray-700 dark:text-gray-200 mb-12">
                    Choose a reviews variation in the admin panel to customize this section.
                </p>
            </div>
        </section>
@endswitch



