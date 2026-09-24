<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\File;
use App\Models\Item;
use App\Models\Media;
use App\Models\SubscriptionPlan;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'role' => 'user',
        ]);

        $categories = collect([
            ['name' => 'AI Prompts', 'slug' => 'ai-prompts'],
            ['name' => 'Design', 'slug' => 'design'],
            ['name' => 'Development', 'slug' => 'development'],
            ['name' => 'Marketing', 'slug' => 'marketing'],
            ['name' => 'Productivity', 'slug' => 'productivity'],
            ['name' => 'Business', 'slug' => 'business'],
            ['name' => 'Writing', 'slug' => 'writing'],
            ['name' => 'Education', 'slug' => 'education'],
        ])->mapWithKeys(
            fn (array $data) => [
                $data['slug'] => Category::create($data),
            ]
        );

        $tags = collect([
            ['name' => 'AI', 'slug' => 'ai'],
            ['name' => 'ChatGPT', 'slug' => 'chatgpt'],
            ['name' => 'Gemini', 'slug' => 'gemini'],
            ['name' => 'Marketing', 'slug' => 'marketing'],
            ['name' => 'Copywriting', 'slug' => 'copywriting'],
            ['name' => 'UI/UX', 'slug' => 'ui-ux'],
            ['name' => 'Branding', 'slug' => 'branding'],
            ['name' => 'Laravel', 'slug' => 'laravel'],
            ['name' => 'Next.js', 'slug' => 'nextjs'],
            ['name' => 'React', 'slug' => 'react'],
            ['name' => 'Productivity', 'slug' => 'productivity'],
            ['name' => 'Business', 'slug' => 'business'],
        ])->mapWithKeys(
            fn (array $data) => [
                $data['slug'] => Tag::create($data),
            ]
        );

        $subscriptionPlans = [
            [
                'name' => 'Monthly',
                'duration_months' => 1,
                'price' => 99000,
                'is_active' => true,
            ],
            [
                'name' => 'Quarterly',
                'duration_months' => 3,
                'price' => 249000,
                'is_active' => true,
            ],
            [
                'name' => 'Yearly',
                'duration_months' => 12,
                'price' => 799000,
                'is_active' => true,
            ],
        ];

        foreach ($subscriptionPlans as $plan) {
            SubscriptionPlan::create($plan);
        }

        $items = [
            [
                'title' => 'Free AI Starter Prompt',
                'slug' => 'free-ai-starter-prompt',
                'description' => 'A free starter prompt for testing the public product flow.',
                'content' => 'Create a concise and clear product description for [PRODUCT]. Include the main benefit, target audience, and a short call to action.',
                'is_visible' => true,
                'access_type' => 'free',
                'price' => 0,
                'category' => 'ai-prompts',
                'tags' => ['ai', 'chatgpt'],
            ],
            [
                'title' => 'ChatGPT Marketing Campaign Prompt',
                'slug' => 'chatgpt-marketing-campaign-prompt',
                'description' => 'A reusable prompt for planning digital marketing campaigns.',
                'content' => 'Create a complete marketing campaign for [PRODUCT]. Define the audience, positioning, campaign message, channels, content ideas, and success metrics.',
                'is_visible' => true,
                'access_type' => 'paid',
                'price' => 49000,
                'category' => 'marketing',
                'tags' => ['chatgpt', 'marketing', 'copywriting'],
            ],
            [
                'title' => 'Gemini Research Assistant',
                'slug' => 'gemini-research-assistant',
                'description' => 'A structured prompt for research and information analysis.',
                'content' => 'Act as a research assistant. Analyze the provided topic, identify the key questions, organize the available information, and present the findings with clear sections.',
                'is_visible' => true,
                'access_type' => 'paid',
                'price' => 79000,
                'category' => 'ai-prompts',
                'tags' => ['gemini', 'ai'],
            ],
            [
                'title' => 'Social Media Caption Generator',
                'slug' => 'social-media-caption-generator',
                'description' => 'Generate platform-ready social media captions from a simple brief.',
                'content' => 'Write five social media captions for [PRODUCT]. Keep each caption engaging, concise, and adapted to the intended audience.',
                'is_visible' => true,
                'access_type' => 'free',
                'price' => 0,
                'category' => 'marketing',
                'tags' => ['marketing', 'copywriting', 'chatgpt'],
            ],
            [
                'title' => 'Brand Voice Prompt',
                'slug' => 'brand-voice-prompt',
                'description' => 'Build a consistent writing style for your brand.',
                'content' => 'Define a brand voice for [BRAND]. Describe tone, vocabulary, sentence style, phrases to use, phrases to avoid, and provide examples.',
                'is_visible' => true,
                'access_type' => 'paid',
                'price' => 99000,
                'category' => 'design',
                'tags' => ['branding', 'copywriting', 'ai'],
            ],
            [
                'title' => 'UI Design Brief Generator',
                'slug' => 'ui-design-brief-generator',
                'description' => 'Create structured UI/UX design briefs for digital products.',
                'content' => 'Create a UI/UX design brief for [PRODUCT]. Define users, goals, information architecture, key screens, interactions, visual direction, and accessibility requirements.',
                'is_visible' => true,
                'access_type' => 'paid',
                'price' => 129000,
                'category' => 'design',
                'tags' => ['ui-ux', 'branding'],
            ],
            [
                'title' => 'SaaS Dashboard UI Guide',
                'slug' => 'saas-dashboard-ui-guide',
                'description' => 'A practical guide for planning modern SaaS dashboard interfaces.',
                'content' => 'Plan a modern SaaS dashboard for [PRODUCT]. Define navigation, dashboard sections, primary actions, empty states, loading states, and responsive behavior.',
                'is_visible' => true,
                'access_type' => 'paid',
                'price' => 159000,
                'category' => 'design',
                'tags' => ['ui-ux', 'react'],
            ],
            [
                'title' => 'Landing Page Copy Framework',
                'slug' => 'landing-page-copy-framework',
                'description' => 'A reusable framework for writing high-quality landing page copy.',
                'content' => 'Create landing page copy for [PRODUCT] with a headline, supporting statement, benefits, feature sections, objections, social proof placeholders, and call to action.',
                'is_visible' => true,
                'access_type' => 'paid',
                'price' => 99000,
                'category' => 'writing',
                'tags' => ['copywriting', 'marketing'],
            ],
            [
                'title' => 'Laravel API Starter Guide',
                'slug' => 'laravel-api-starter-guide',
                'description' => 'A practical starting structure for building Laravel APIs.',
                'content' => 'Define a clean Laravel API structure including routes, controllers, form requests, resources, services, authentication, validation, and testing boundaries.',
                'is_visible' => true,
                'access_type' => 'paid',
                'price' => 190000,
                'category' => 'development',
                'tags' => ['laravel', 'business'],
            ],
            [
                'title' => 'Next.js App Structure Guide',
                'slug' => 'nextjs-app-structure-guide',
                'description' => 'A practical structure for scalable Next.js applications.',
                'content' => 'Design a maintainable Next.js application structure with routes, components, shared UI, API services, state management, authentication, and reusable utilities.',
                'is_visible' => true,
                'access_type' => 'paid',
                'price' => 190000,
                'category' => 'development',
                'tags' => ['nextjs', 'react'],
            ],
            [
                'title' => 'React Component Checklist',
                'slug' => 'react-component-checklist',
                'description' => 'A checklist for building focused and reusable React components.',
                'content' => 'Review a React component for responsibility, props, state, accessibility, responsive behavior, reuse opportunities, and unnecessary complexity.',
                'is_visible' => true,
                'access_type' => 'free',
                'price' => 0,
                'category' => 'development',
                'tags' => ['react', 'ui-ux'],
            ],
            [
                'title' => 'Developer Documentation Template',
                'slug' => 'developer-documentation-template',
                'description' => 'A reusable structure for clear technical documentation.',
                'content' => 'Create developer documentation with overview, prerequisites, installation, configuration, usage examples, API reference, troubleshooting, and deployment notes.',
                'is_visible' => true,
                'access_type' => 'free',
                'price' => 0,
                'category' => 'development',
                'tags' => ['laravel', 'nextjs'],
            ],
            [
                'title' => 'Weekly Productivity Planner',
                'slug' => 'weekly-productivity-planner',
                'description' => 'A simple framework for planning a focused productive week.',
                'content' => 'Create a weekly plan with the most important goals, supporting tasks, time blocks, priorities, risks, and an end-of-week review.',
                'is_visible' => true,
                'access_type' => 'free',
                'price' => 0,
                'category' => 'productivity',
                'tags' => ['productivity'],
            ],
            [
                'title' => 'Business Strategy Canvas',
                'slug' => 'business-strategy-canvas',
                'description' => 'A structured framework for defining a digital business strategy.',
                'content' => 'Create a business strategy canvas covering customer segments, value proposition, channels, revenue streams, key activities, resources, and costs.',
                'is_visible' => true,
                'access_type' => 'paid',
                'price' => 149000,
                'category' => 'business',
                'tags' => ['business', 'productivity'],
            ],
            [
                'title' => 'Email Copy Pack',
                'slug' => 'email-copy-pack',
                'description' => 'Reusable email copy structures for digital products.',
                'content' => 'Create email templates for welcome, product launch, abandoned cart, purchase confirmation, feature announcement, and re-engagement campaigns.',
                'is_visible' => true,
                'access_type' => 'paid',
                'price' => 129000,
                'category' => 'marketing',
                'tags' => ['copywriting', 'marketing'],
            ],
            [
                'title' => 'SEO Blog Outline Generator',
                'slug' => 'seo-blog-outline-generator',
                'description' => 'Generate structured SEO-friendly blog outlines.',
                'content' => 'Create an SEO-focused blog outline for [TOPIC]. Include search intent, title ideas, headings, supporting questions, internal-link opportunities, and a conclusion.',
                'is_visible' => true,
                'access_type' => 'paid',
                'price' => 79000,
                'category' => 'writing',
                'tags' => ['ai', 'copywriting', 'marketing'],
            ],
            [
                'title' => 'Course Outline Generator',
                'slug' => 'course-outline-generator',
                'description' => 'Plan structured online courses from a single topic.',
                'content' => 'Create a complete course outline for [TOPIC]. Define modules, lessons, learning objectives, exercises, projects, and assessment criteria.',
                'is_visible' => true,
                'access_type' => 'paid',
                'price' => 109000,
                'category' => 'education',
                'tags' => ['ai', 'productivity'],
            ],
            [
                'title' => 'Lesson Planner',
                'slug' => 'lesson-planner',
                'description' => 'Create structured lesson plans for different learning levels.',
                'content' => 'Create a lesson plan for [TOPIC] including learning objectives, introduction, teaching activities, practice, assessment, and homework.',
                'is_visible' => true,
                'access_type' => 'free',
                'price' => 0,
                'category' => 'education',
                'tags' => ['productivity', 'ai'],
            ],
            [
                'title' => 'AI Product Description Writer',
                'slug' => 'ai-product-description-writer',
                'description' => 'Generate persuasive descriptions for digital products.',
                'content' => 'Write a clear digital-product description for [PRODUCT]. Explain what it contains, who it is for, the main benefits, and what the buyer receives.',
                'is_visible' => true,
                'access_type' => 'paid',
                'price' => 59000,
                'category' => 'writing',
                'tags' => ['ai', 'copywriting'],
            ],
            [
                'title' => 'Startup Idea Validator',
                'slug' => 'startup-idea-validator',
                'description' => 'Evaluate a product idea using a structured validation framework.',
                'content' => 'Analyze [IDEA] using customer problem, target audience, existing alternatives, differentiation, risks, validation experiments, and measurable success criteria.',
                'is_visible' => true,
                'access_type' => 'paid',
                'price' => 139000,
                'category' => 'business',
                'tags' => ['business', 'ai'],
            ],
            [
                'title' => 'Product Roadmap Planner',
                'slug' => 'product-roadmap-planner',
                'description' => 'Turn product goals into a practical roadmap.',
                'content' => 'Create a product roadmap for [PRODUCT] with goals, milestones, priorities, dependencies, risks, and measurable outcomes.',
                'is_visible' => true,
                'access_type' => 'paid',
                'price' => 119000,
                'category' => 'business',
                'tags' => ['business', 'productivity'],
            ],
            [
                'title' => 'Focus Session Planner',
                'slug' => 'focus-session-planner',
                'description' => 'A lightweight framework for planning distraction-free work sessions.',
                'content' => 'Plan a focused work session with one primary outcome, supporting tasks, estimated durations, distractions to remove, and a short review.',
                'is_visible' => true,
                'access_type' => 'free',
                'price' => 0,
                'category' => 'productivity',
                'tags' => ['productivity'],
            ],
            [
                'title' => 'Meeting Summary Assistant',
                'slug' => 'meeting-summary-assistant',
                'description' => 'Turn meeting notes into clear decisions and action items.',
                'content' => 'Summarize the provided meeting notes into decisions, open questions, action items, owners, deadlines, and follow-up topics.',
                'is_visible' => true,
                'access_type' => 'free',
                'price' => 0,
                'category' => 'productivity',
                'tags' => ['ai', 'productivity'],
            ],
            [
                'title' => 'Brand Naming Workshop',
                'slug' => 'brand-naming-workshop',
                'description' => 'A structured process for generating and evaluating brand names.',
                'content' => 'Generate brand name ideas for [BUSINESS]. Group them by style and explain the concept, memorability, pronunciation, and positioning behind each option.',
                'is_visible' => true,
                'access_type' => 'paid',
                'price' => 89000,
                'category' => 'design',
                'tags' => ['branding', 'ai'],
            ],
            [
                'title' => 'Visual Identity Brief',
                'slug' => 'visual-identity-brief',
                'description' => 'Define the foundation of a consistent visual identity.',
                'content' => 'Create a visual identity brief covering brand personality, audience, color direction, typography, imagery, layout principles, and usage guidelines.',
                'is_visible' => true,
                'access_type' => 'paid',
                'price' => 149000,
                'category' => 'design',
                'tags' => ['branding', 'ui-ux'],
            ],
            [
                'title' => 'Mobile App UX Audit',
                'slug' => 'mobile-app-ux-audit',
                'description' => 'A structured checklist for reviewing mobile app user experience.',
                'content' => 'Audit the provided mobile app flow for navigation, clarity, accessibility, interaction patterns, feedback, error handling, and friction points.',
                'is_visible' => true,
                'access_type' => 'paid',
                'price' => 139000,
                'category' => 'design',
                'tags' => ['ui-ux', 'react'],
            ],
            [
                'title' => 'API Error Message Guide',
                'slug' => 'api-error-message-guide',
                'description' => 'Create consistent and useful API error responses.',
                'content' => 'Define a consistent API error strategy covering validation errors, authentication failures, authorization failures, not-found responses, conflicts, and server errors.',
                'is_visible' => true,
                'access_type' => 'paid',
                'price' => 99000,
                'category' => 'development',
                'tags' => ['laravel', 'nextjs'],
            ],
            [
                'title' => 'Content Calendar Generator',
                'slug' => 'content-calendar-generator',
                'description' => 'Build a practical content calendar for a digital product.',
                'content' => 'Create a 30-day content calendar for [PRODUCT] with topics, content formats, target audience, publishing channels, and calls to action.',
                'is_visible' => true,
                'access_type' => 'paid',
                'price' => 109000,
                'category' => 'marketing',
                'tags' => ['marketing', 'copywriting'],
            ],
            [
                'title' => 'Customer Persona Builder',
                'slug' => 'customer-persona-builder',
                'description' => 'Create detailed customer personas for product planning and marketing.',
                'content' => 'Create a customer persona for [PRODUCT] covering demographics, goals, problems, motivations, objections, buying triggers, and preferred channels.',
                'is_visible' => true,
                'access_type' => 'paid',
                'price' => 89000,
                'category' => 'business',
                'tags' => ['business', 'marketing'],
            ],
            [
                'title' => 'Study Plan Generator',
                'slug' => 'study-plan-generator',
                'description' => 'Create a realistic study plan around a target and deadline.',
                'content' => 'Create a study plan for [SUBJECT] from [START DATE] to [DEADLINE]. Break the work into sessions, reviews, practice, and progress checkpoints.',
                'is_visible' => true,
                'access_type' => 'free',
                'price' => 0,
                'category' => 'education',
                'tags' => ['productivity', 'ai'],
            ],
            [
                'title' => 'Writing Style Editor',
                'slug' => 'writing-style-editor',
                'description' => 'Improve clarity and consistency while preserving the original meaning.',
                'content' => 'Edit the provided text for clarity, structure, tone, grammar, and consistency while preserving the original meaning and important details.',
                'is_visible' => true,
                'access_type' => 'paid',
                'price' => 69000,
                'category' => 'writing',
                'tags' => ['copywriting', 'ai'],
            ],
            [
                'title' => 'Hidden Test Product',
                'slug' => 'hidden-test-product',
                'description' => 'A hidden product for testing visibility rules.',
                'content' => 'This product is intentionally hidden and exists only for testing visibility and access rules.',
                'is_visible' => false,
                'access_type' => 'paid',
                'price' => 99000,
                'category' => 'development',
                'tags' => ['laravel'],
            ],
        ];

        foreach ($items as $data) {
            $item = Item::create([
                'title' => $data['title'],
                'slug' => $data['slug'],
                'description' => $data['description'],
                'content' => $data['content'],
                'is_visible' => $data['is_visible'],
                'access_type' => $data['access_type'],
                'price' => $data['price'],
            ]);

            $item->categories()->attach(
                $categories[$data['category']]->id
            );

            $item->tags()->attach(
                collect($data['tags'])
                    ->map(fn (string $slug) => $tags[$slug]->id)
                    ->all()
            );

            $file = File::factory()->create();

            Media::create([
                'item_id' => $item->id,
                'file_id' => $file->id,
                'is_primary' => true,
                'sort_order' => 0,
            ]);
        }
    }
}
