<?php

namespace Tests\Feature;

use App\Jobs\PublishArticleToFacebook;
use App\Mail\ContactSubmissionNotification;
use App\Models\Advertisement;
use App\Models\Article;
use App\Models\Category;
use App\Models\Comment;
use App\Models\ContactMessage;
use App\Models\LiveEvent;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class NewsroomTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_and_category_navigation_render(): void
    {
        $category = Category::create(['name' => 'Politics', 'slug' => 'politics', 'in_menu' => true]);
        $article = $this->publishedArticle($category);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee($article->title)
            ->assertSee(route('category.show', 'politics'))
            ->assertSee('<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">', false)
            ->assertSee('NewsMediaOrganization')
            ->assertSee('SearchAction')
            ->assertSee('@context')
            ->assertSee('https://facebook.com/newslanemw', false)
            ->assertSee('data-theme-toggle', false)
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Security-Policy');

        $this->get(route('category.show', 'politics'))
            ->assertOk()
            ->assertSee($article->title);
    }

    public function test_africa_category_includes_malawi_and_editorially_selected_african_stories(): void
    {
        Category::create(['name' => 'Africa', 'slug' => 'africa']);
        $malawi = Category::create(['name' => 'Malawi', 'slug' => 'malawi']);
        $politics = Category::create(['name' => 'Politics', 'slug' => 'politics']);
        $international = Category::create(['name' => 'International', 'slug' => 'international']);
        $malawiStory = $this->publishedArticle($malawi, ['title' => 'Malawi policy update']);
        $africanPoliticsStory = $this->publishedArticle($politics, [
            'title' => 'Regional election update',
            'include_in_africa' => true,
        ]);
        $internationalStory = $this->publishedArticle($international, [
            'title' => 'Kuala Lumpur court update',
        ]);
        $ordinaryPoliticsStory = $this->publishedArticle($politics, [
            'title' => 'European parliament debate',
        ]);

        $this->get(route('category.show', 'africa'))
            ->assertOk()
            ->assertSee($malawiStory->title)
            ->assertSee($africanPoliticsStory->title)
            ->assertDontSee($internationalStory->title)
            ->assertDontSee($ordinaryPoliticsStory->title);
    }

    public function test_homepage_sections_use_rotating_layouts_and_africa_tagline(): void
    {
        $titles = ['Africa layout story', 'Business layout story', 'Health layout story', 'Culture layout story'];

        foreach ([
            ['name' => 'Africa', 'slug' => 'africa'],
            ['name' => 'Business', 'slug' => 'business'],
            ['name' => 'Health', 'slug' => 'health'],
            ['name' => 'Culture', 'slug' => 'culture'],
        ] as $index => $attributes) {
            $category = Category::create($attributes + ['show_on_homepage' => true]);
            $this->publishedArticle($category, ['title' => $titles[$index]]);
        }

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('From Africa to the world.')
            ->assertSee('category-home-section--feature', false)
            ->assertSee('category-home-section--list', false)
            ->assertSee('category-home-section--mosaic', false)
            ->assertSee('category-home-section--scroll', false);
    }

    public function test_administrator_list_pages_are_paginated(): void
    {
        $admin = User::create([
            'name' => 'Pagination Administrator',
            'email' => 'pagination-admin@example.test',
            'password' => Hash::make('A-long-test-password-2026!'),
            'role' => 'admin',
        ]);

        foreach (range(1, 16) as $number) {
            Category::create([
                'name' => 'Pagination category '.$number,
                'slug' => 'pagination-category-'.$number,
                'position' => $number,
                'in_menu' => false,
            ]);
            Advertisement::create([
                'title' => 'Pagination advertisement '.$number,
                'image_path' => 'ads/test-'.$number.'.jpg',
                'target_url' => 'https://example.test/'.$number,
                'placement' => 'homepage',
                'active' => true,
            ]);
        }

        foreach (range(1, 11) as $number) {
            LiveEvent::create([
                'title' => 'Pagination live event '.$number,
                'slug' => 'pagination-live-event-'.$number,
                'summary' => 'A live-event summary for pagination testing.',
                'status' => 'closed',
                'started_at' => now()->subMinutes($number),
            ]);
        }

        $this->actingAs($admin)->get(route('admin.categories.index'))
            ->assertOk()
            ->assertDontSee('Pagination category 16')
            ->assertSee(route('admin.categories.index', ['page' => 2]), false);

        $this->get(route('admin.categories.index', ['page' => 2]))
            ->assertOk()
            ->assertSee('Pagination category 16');

        $this->get(route('admin.ads.index'))
            ->assertOk()
            ->assertDontSee('Pagination advertisement 16')
            ->assertSee(route('admin.ads.index', ['page' => 2]), false);

        $this->get(route('admin.ads.index', ['page' => 2]))
            ->assertOk()
            ->assertSee('Pagination advertisement 16');

        $this->get(route('admin.live.index'))
            ->assertOk()
            ->assertDontSee('Pagination live event 11')
            ->assertSee(route('admin.live.index', ['page' => 2]), false);

        $this->get(route('admin.live.index', ['page' => 2]))
            ->assertOk()
            ->assertSee('Pagination live event 11');
    }

    public function test_topbar_shows_africa_forecast_and_malawi_conditions_and_caches_provider_response(): void
    {
        config(['weather.open_meteo.api_key' => 'test-commercial-weather-key']);
        $this->clearWeatherCache();
        Http::preventStrayRequests();
        Http::fake([
            'customer-api.open-meteo.com/v1/forecast*' => Http::response($this->weatherForecastPayload()),
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Africa forecast', false)
            ->assertSee('Lusaka 26°C', false)
            ->assertSee('Across Africa')
            ->assertSee('Malawi')
            ->assertSee('Current conditions')
            ->assertSee('3-day forecast');

        $this->get(route('contact'))
            ->assertOk()
            ->assertSee('Across Africa')
            ->assertSee('Blantyre')
            ->assertSee('Lusaka')
            ->assertSee('3-day forecast', false);

        $this->get(route('weather.widget'))
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertSee('Lusaka 26°C', false);

        Http::assertSentCount(1);
        Http::assertSent(fn ($request): bool => str_contains($request->url(), 'customer-api.open-meteo.com/v1/forecast')
            && $request['apikey'] === 'test-commercial-weather-key');
    }

    public function test_weather_widget_reports_unavailable_without_a_provider_key_or_live_response(): void
    {
        config(['weather.open_meteo.api_key' => null]);
        $this->clearWeatherCache();
        Http::preventStrayRequests();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Live weather is temporarily unavailable.')
            ->assertSee('OFFLINE');

        Http::assertNothingSent();
    }

    public function test_weather_widget_uses_the_last_available_forecast_if_the_provider_fails(): void
    {
        config(['weather.open_meteo.api_key' => 'test-commercial-weather-key']);
        $this->clearWeatherCache();
        $requestCount = 0;
        Http::preventStrayRequests();
        Http::fake([
            'customer-api.open-meteo.com/v1/forecast*' => function () use (&$requestCount) {
                $requestCount++;

                return $requestCount === 1
                    ? Http::response($this->weatherForecastPayload())
                    : Http::response([], 503);
            },
        ]);

        $this->get(route('home'))->assertOk()->assertSee('Lusaka 26°C', false);
        Cache::forget(config('weather.open_meteo.cache_key'));

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Showing last available update')
            ->assertSee('Lusaka 26°C', false);
    }

    public function test_newsroom_seeds_sections_for_jobs_technology_entertainment_and_business(): void
    {
        $this->seed();

        foreach (['malawi', 'politics', 'africa', 'international', 'business', 'health', 'sports', 'entertainment', 'technology', 'jobs-internships', 'culture', 'opinion'] as $slug) {
            $this->assertDatabaseHas('categories', ['slug' => $slug]);
        }

        foreach (['international', 'business', 'entertainment', 'technology', 'jobs-internships'] as $slug) {
            $this->assertDatabaseHas('categories', ['slug' => $slug, 'show_on_homepage' => true]);
        }
    }

    public function test_homepage_shows_configured_sections_breaking_thumbnails_carousel_and_live_video(): void
    {
        $category = Category::create([
            'name' => 'Entertainment',
            'slug' => 'entertainment',
            'show_on_homepage' => true,
        ]);
        $first = $this->publishedArticle($category, [
            'title' => 'Featured newsroom story one',
            'slug' => 'featured-newsroom-story-one',
            'featured' => true,
            'breaking' => true,
            'cover_image' => 'articles/one.jpg',
            'image_alt' => 'An editorial photograph of a public event',
            'image_caption' => 'Illustrative image from a 2025 Malawi-Zambia friendly.',
            'image_credit' => 'ChaloNiZambia · CC BY-SA 4.0 · Resized',
            'image_credit_url' => 'https://commons.wikimedia.org/wiki/File:2025-02-25_Women_Association_Football_Zambia_-_Malawi_match_27.jpg',
        ]);
        $this->publishedArticle($category, [
            'title' => 'Featured newsroom story two',
            'slug' => 'featured-newsroom-story-two',
            'featured' => true,
        ]);
        $this->publishedArticle($category, [
            'title' => 'Entertainment section article',
            'slug' => 'entertainment-section-article',
        ]);
        $event = LiveEvent::create([
            'title' => 'Live newsroom video',
            'slug' => 'live-newsroom-video',
            'summary' => 'An authorized privacy-enhanced newsroom video.',
            'status' => 'live',
            'video_embed_url' => 'https://www.youtube-nocookie.com/embed/abcdefghijk',
        ]);

        $response = $this->get(route('home'))
            ->assertOk()
            ->assertSee('data-carousel', false)
            ->assertSee('data-carousel-slide', false)
            ->assertSee('Breaking')
            ->assertSee('Most read')
            ->assertSee('Entertainment')
            ->assertDontSee('Illustrative image')
            ->assertDontSee($first->image_caption)
            ->assertSee('Photo taken by ', false)
            ->assertSee('ChaloNiZambia · CC BY-SA 4.0')
            ->assertDontSee('resized from original')
            ->assertSee($first->image_credit_url, false)
            ->assertSee($event->video_embed_url)
            ->assertHeader('Content-Security-Policy');

        $this->assertSame(3, substr_count($response->getContent(), 'data-carousel-slide'));
    }

    public function test_public_story_uses_root_slug_and_search_metadata(): void
    {
        $article = $this->publishedArticle(overrides: ['published_at' => now()->startOfDay()]);

        $this->get(route('articles.show', $article->slug))
            ->assertOk()
            ->assertSee('<meta property="og:title" content="'.$article->title.'">', false)
            ->assertSee('NewsArticle')
            ->assertSee('@context')
            ->assertSee(route('articles.show', $article->slug), false)
            ->assertDontSee('00:00 CAT');

        $this->get(route('search', ['q' => 'Malawi']))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertSee($article->title);

        $this->get(route('sitemap'))
            ->assertOk()
            ->assertSee(route('articles.show', $article->slug), false);
    }

    public function test_editors_can_embed_youtube_videos_without_accepting_external_frames(): void
    {
        $editor = User::create([
            'name' => 'Desk Editor',
            'email' => 'video-editor@example.test',
            'password' => Hash::make('A-long-test-password-2026!'),
            'role' => 'editor',
        ]);
        $category = Category::create(['name' => 'Opinion', 'slug' => 'opinion']);
        $youtubeEmbed = 'https://www.youtube-nocookie.com/embed/7yFZ0NfGmrU';

        $this->actingAs($editor)->get(route('admin.articles.create'))
            ->assertOk()
            ->assertSee('name="video_url"', false);

        $this->actingAs($editor)->post(route('admin.articles.store'), [
            'title' => 'Afrobarometer Round 11 results at BICC',
            'category_id' => $category->id,
            'excerpt' => 'NEWSLANE’s video coverage of the Afrobarometer Round 11 results dissemination in Lilongwe.',
            'body' => 'Watch NEWSLANE’s coverage of the Round 11 results dissemination at BICC in Lilongwe.',
            'status' => 'published',
            'video_url' => 'https://youtu.be/7yFZ0NfGmrU',
        ])->assertRedirect(route('admin.articles.index'));

        $article = Article::where('title', 'Afrobarometer Round 11 results at BICC')->firstOrFail();
        $this->assertSame($youtubeEmbed, $article->video_embed_url);
        $response = $this->get(route('articles.show', $article->slug))
            ->assertOk()
            ->assertSee('<iframe src="'.$youtubeEmbed.'"', false)
            ->assertSee('title="Afrobarometer Round 11 results at BICC video"', false)
            ->assertHeader('Content-Security-Policy');
        $this->assertStringContainsString('https://www.youtube-nocookie.com', $response->headers->get('Content-Security-Policy'));

        $this->put(route('admin.articles.update', $article), [
            'title' => 'Afrobarometer Round 11 results at BICC',
            'category_id' => $category->id,
            'excerpt' => 'NEWSLANE’s video coverage of the Afrobarometer Round 11 results dissemination in Lilongwe.',
            'body' => 'Watch NEWSLANE’s coverage of the Round 11 results dissemination at BICC in Lilongwe.',
            'status' => 'published',
            'video_url' => 'https://video-attacker.example/embed/7yFZ0NfGmrU',
        ])->assertSessionHasErrors('video_url');

        $this->assertSame($youtubeEmbed, $article->fresh()->video_embed_url);
    }

    public function test_drafts_and_future_articles_are_not_public(): void
    {
        $category = Category::create(['name' => 'World', 'slug' => 'world']);
        $draft = $this->publishedArticle($category, ['status' => 'draft']);
        $future = $this->publishedArticle($category, ['published_at' => now()->addDay()]);

        $this->get(route('articles.show', $draft->slug))->assertNotFound();
        $this->get(route('articles.show', $future->slug))->assertNotFound();
        $this->get(route('home'))->assertDontSee($draft->title)->assertDontSee($future->title);
    }

    public function test_editorial_dashboard_requires_authorized_staff(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->get(route('admin.login'))->assertOk();

        $reader = User::create([
            'name' => 'Reader',
            'email' => 'reader@example.test',
            'password' => Hash::make('A-long-test-password-2026!'),
            'role' => 'reader',
        ]);

        $this->actingAs($reader)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_active_editor_can_update_profile_and_must_confirm_current_password(): void
    {
        $editor = User::create([
            'name' => 'Desk Editor',
            'email' => 'profile-editor@example.test',
            'password' => Hash::make('A-long-test-password-2026!'),
            'role' => 'editor',
        ]);

        $this->actingAs($editor)->get(route('admin.profile.edit'))
            ->assertOk()
            ->assertSee('Update your details');

        $this->put(route('admin.profile.update'), [
            'name' => 'Updated Desk Editor',
            'email' => 'updated-editor@example.test',
            'bio' => 'Reporting on public-interest stories in Malawi.',
            'current_password' => 'A-long-test-password-2026!',
        ])->assertRedirect()
            ->assertSessionHas('status', 'Your profile has been updated.');

        $this->assertDatabaseHas('users', [
            'id' => $editor->id,
            'name' => 'Updated Desk Editor',
            'email' => 'updated-editor@example.test',
            'bio' => 'Reporting on public-interest stories in Malawi.',
        ]);

        $this->put(route('admin.profile.update'), [
            'name' => 'Unchanged Desk Editor',
            'email' => 'unverified-editor@example.test',
            'bio' => 'This change must not be stored.',
            'current_password' => 'incorrect-password',
        ])->assertSessionHasErrors('current_password');

        $this->assertDatabaseMissing('users', ['email' => 'unverified-editor@example.test']);
        $this->assertDatabaseHas('users', ['id' => $editor->id, 'name' => 'Updated Desk Editor']);
    }

    public function test_reporters_and_interns_can_submit_only_their_own_drafts(): void
    {
        $category = Category::create(['name' => 'Malawi', 'slug' => 'malawi']);
        $reporter = User::create([
            'name' => 'Staff Reporter',
            'email' => 'reporter@example.test',
            'password' => Hash::make('A-long-test-password-2026!'),
            'role' => 'reporter',
        ]);
        $intern = User::create([
            'name' => 'Newsroom Intern',
            'email' => 'intern@example.test',
            'password' => Hash::make('A-long-test-password-2026!'),
            'role' => 'intern',
        ]);

        $this->actingAs($reporter)->get(route('admin.articles.create'))
            ->assertOk()
            ->assertSee('Your submissions are private drafts for editorial review.')
            ->assertDontSee('name="published_at"', false)
            ->assertDontSee('name="featured"', false);

        $this->post(route('admin.articles.store'), [
            'title' => 'Reporter original field notes',
            'category_id' => $category->id,
            'excerpt' => 'A concise summary supplied by a newsroom reporter.',
            'body' => 'An original account submitted for desk review.',
            'status' => 'draft',
        ])->assertRedirect(route('admin.articles.index'));

        $reporterDraft = Article::where('title', 'Reporter original field notes')->firstOrFail();
        $this->assertSame('draft', $reporterDraft->status);
        $this->assertSame($reporter->id, $reporterDraft->user_id);
        $this->assertNull($reporterDraft->published_at);
        $this->assertFalse($reporterDraft->featured);

        $this->put(route('admin.articles.update', $reporterDraft), [
            'title' => 'Reporter tries to publish',
            'category_id' => $category->id,
            'excerpt' => 'A concise summary supplied by a newsroom reporter.',
            'body' => 'An original account submitted for desk review.',
            'status' => 'published',
        ])->assertSessionHasErrors('status');

        $this->put(route('admin.articles.update', $reporterDraft), [
            'title' => 'Reporter attempts to feature a story',
            'category_id' => $category->id,
            'excerpt' => 'A concise summary supplied by a newsroom reporter.',
            'body' => 'An original account submitted for desk review.',
            'status' => 'draft',
            'featured' => '1',
        ])->assertSessionHasErrors('featured');

        $otherDraft = $this->publishedArticle($category, ['status' => 'draft', 'user_id' => $intern->id, 'published_at' => null]);
        $this->get(route('admin.articles.edit', $otherDraft))->assertForbidden();
        $this->actingAs($intern)->get(route('admin.articles.edit', $reporterDraft))->assertForbidden();
        $this->delete(route('admin.articles.destroy', $reporterDraft))->assertForbidden();
        $this->actingAs($intern)->get(route('admin.comments.index'))->assertForbidden();
        $this->get(route('admin.ads.index'))->assertForbidden();
        $this->get(route('admin.live.index'))->assertForbidden();
    }

    public function test_only_administrators_can_manage_google_adsense_and_the_loader_respects_the_setting(): void
    {
        $editor = User::create([
            'name' => 'Desk Editor',
            'email' => 'adsense-editor@example.test',
            'password' => Hash::make('A-long-test-password-2026!'),
            'role' => 'editor',
        ]);
        $admin = User::create([
            'name' => 'Site Administrator',
            'email' => 'adsense-admin@example.test',
            'password' => Hash::make('A-long-test-password-2026!'),
            'role' => 'admin',
        ]);
        config(['services.adsense.client_id' => 'ca-pub-4801370358631853']);

        $this->actingAs($editor)->get(route('admin.adsense.edit'))->assertForbidden();

        $this->actingAs($admin)->get(route('admin.adsense.edit'))
            ->assertOk()
            ->assertSee('ca-pub-4801370358631853')
            ->assertDontSee('pagead2.googlesyndication.com/pagead/js/adsbygoogle.js');

        $this->actingAs($admin)->put(route('admin.adsense.update'), ['enabled' => '0'])->assertRedirect();
        $this->assertDatabaseHas('adsense_settings', ['id' => 1, 'enabled' => false]);
        $this->get(route('home'))->assertOk()->assertDontSee('pagead2.googlesyndication.com/pagead/js/adsbygoogle.js');

        $this->put(route('admin.adsense.update'), ['enabled' => '1'])->assertRedirect();
        $response = $this->get(route('home'))
            ->assertOk()
            ->assertSee('https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-4801370358631853', false)
            ->assertHeader('Content-Security-Policy');
        $this->assertStringContainsString('https://googleads.g.doubleclick.net', $response->headers->get('Content-Security-Policy'));
    }

    public function test_editor_can_publish_and_reader_comments_are_moderated(): void
    {
        config([
            'app.url' => 'https://newslane.africa',
            'services.facebook_page.page_id' => '1234567890',
            'services.facebook_page.page_access_token' => 'test-page-access-token',
            'services.facebook_page.graph_api_version' => 'v27.0',
        ]);
        Queue::fake([PublishArticleToFacebook::class]);

        $editor = User::create([
            'name' => 'Desk Editor',
            'email' => 'editor@example.test',
            'password' => Hash::make('A-long-test-password-2026!'),
            'role' => 'editor',
        ]);
        $category = Category::create(['name' => 'Malawi', 'slug' => 'malawi']);
        Storage::fake('public');

        $this->actingAs($editor)->post(route('admin.articles.store'), [
            'title' => 'A newsroom test story',
            'category_id' => $category->id,
            'excerpt' => 'A concise summary for the front page.',
            'body' => 'This is a sufficiently long newsroom test story.',
            'status' => 'published',
            'cover' => UploadedFile::fake()->image('cover.jpg'),
            'image_alt' => 'A descriptive image alt text.',
            'image_caption' => 'An illustrative newsroom photograph.',
            'image_credit' => 'Photographer Name · CC BY-SA 4.0 · Resized',
            'image_credit_url' => 'https://commons.wikimedia.org/wiki/File:Example.jpg',
        ])->assertRedirect(route('admin.articles.index'));

        $article = Article::where('title', 'A newsroom test story')->firstOrFail();
        $this->assertSame('a-newsroom-test-story', $article->slug);
        Storage::disk('public')->assertExists($article->cover_image);
        $this->assertSame('An illustrative newsroom photograph.', $article->image_caption);
        $this->assertSame('Photographer Name · CC BY-SA 4.0 · Resized', $article->image_credit);
        $this->assertSame('https://commons.wikimedia.org/wiki/File:Example.jpg', $article->image_credit_url);
        $this->assertSame('pending', $article->facebook_post_status);
        Queue::assertPushed(PublishArticleToFacebook::class, fn (PublishArticleToFacebook $job): bool => $job->articleId === $article->id);
        $this->get(route('articles.show', $article->slug))
            ->assertOk()
            ->assertSee('<meta property="og:image" content="'.asset('storage/'.$article->cover_image).'">', false)
            ->assertSee('<meta property="og:image:alt" content="A descriptive image alt text.">', false)
            ->assertSee($article->image_caption)
            ->assertSee('Photo taken by ', false)
            ->assertSee('Photographer Name · CC BY-SA 4.0')
            ->assertDontSee('resized from original')
            ->assertSee($article->image_credit_url, false);

        $this->post(route('articles.comments.store', $article->slug), [
            'name' => 'Thoughtful Reader',
            'email' => 'reader@example.test',
            'body' => 'A constructive comment for moderation.',
        ])->assertRedirect();

        $comment = Comment::where('article_id', $article->id)->firstOrFail();
        $this->assertSame('pending', $comment->status);
        $this->get(route('articles.show', $article->slug))->assertDontSee('A constructive comment for moderation.');

        $this->actingAs($editor)->patch(route('admin.comments.approve', $comment))->assertRedirect();
        $this->get(route('articles.show', $article->slug))->assertSee('A constructive comment for moderation.');
    }

    public function test_facebook_job_publishes_article_link_and_saves_the_returned_post_id(): void
    {
        config([
            'app.url' => 'https://newslane.africa',
            'services.facebook_page.page_id' => '1234567890',
            'services.facebook_page.page_access_token' => 'test-page-access-token',
            'services.facebook_page.graph_api_version' => 'v27.0',
        ]);
        Http::preventStrayRequests();
        Http::fake([
            'graph.facebook.com/v27.0/1234567890/feed' => Http::response(['id' => '1234567890_9876543210']),
        ]);
        $article = $this->publishedArticle(overrides: [
            'slug' => 'facebook-link-preview-story',
            'title' => 'A Facebook link preview story',
            'excerpt' => 'A short story summary for Facebook readers.',
        ]);
        $article->forceFill(['facebook_post_status' => 'pending'])->save();

        (new PublishArticleToFacebook($article->id, $article->published_at->timestamp))->handle();

        $article->refresh();
        $this->assertSame('posted', $article->facebook_post_status);
        $this->assertSame('1234567890_9876543210', $article->facebook_post_id);
        $this->assertNotNull($article->facebook_posted_at);
        $this->assertNull($article->facebook_post_error);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://graph.facebook.com/v27.0/1234567890/feed'
            && $request['access_token'] === 'test-page-access-token'
            && $request['message'] === "A Facebook link preview story\n\nA short story summary for Facebook readers."
            && $request['link'] === 'https://newslane.africa/facebook-link-preview-story');

        (new PublishArticleToFacebook($article->id, $article->published_at->timestamp))->handle();
        Http::assertSentCount(1);
    }

    public function test_facebook_job_records_api_rejection_without_exposing_the_access_token(): void
    {
        config([
            'app.url' => 'https://newslane.africa',
            'services.facebook_page.page_id' => '1234567890',
            'services.facebook_page.page_access_token' => 'sensitive-test-page-token',
            'services.facebook_page.graph_api_version' => 'v27.0',
        ]);
        Http::preventStrayRequests();
        Http::fake([
            'graph.facebook.com/v27.0/1234567890/feed' => Http::response(['error' => ['message' => 'Permission denied.']], 403),
        ]);
        $article = $this->publishedArticle();
        $article->forceFill(['facebook_post_status' => 'pending'])->save();

        (new PublishArticleToFacebook($article->id, $article->published_at->timestamp))->handle();

        $article->refresh();
        $this->assertSame('failed', $article->facebook_post_status);
        $this->assertSame('Facebook rejected the post (HTTP 403). Check the Page access token and permissions.', $article->facebook_post_error);
        $this->assertStringNotContainsString('sensitive-test-page-token', $article->facebook_post_error);
        $this->assertNull($article->facebook_post_id);
    }

    public function test_scheduled_articles_queue_facebook_posts_for_their_publication_time(): void
    {
        $this->freezeTime();
        $publishedAt = now()->addHour()->startOfMinute();
        config([
            'services.facebook_page.page_id' => '1234567890',
            'services.facebook_page.page_access_token' => 'test-page-access-token',
            'services.facebook_page.graph_api_version' => 'v27.0',
        ]);
        Queue::fake([PublishArticleToFacebook::class]);
        $editor = User::create([
            'name' => 'Desk Editor',
            'email' => 'scheduled-facebook-editor@example.test',
            'password' => Hash::make('A-long-test-password-2026!'),
            'role' => 'editor',
        ]);
        $category = Category::create(['name' => 'Malawi', 'slug' => 'malawi']);

        $this->actingAs($editor)->post(route('admin.articles.store'), [
            'title' => 'A scheduled Facebook story',
            'category_id' => $category->id,
            'excerpt' => 'A summary for this scheduled story.',
            'body' => 'This scheduled article will be automatically shared when it goes live.',
            'status' => 'published',
            'published_at' => $publishedAt->format('Y-m-d\TH:i'),
        ])->assertRedirect(route('admin.articles.index'));

        $article = Article::where('title', 'A scheduled Facebook story')->firstOrFail();
        Queue::assertPushed(
            PublishArticleToFacebook::class,
            fn (PublishArticleToFacebook $job): bool => $job->articleId === $article->id
                && $job->publicationTimestamp === $publishedAt->timestamp
                && $job->delay instanceof \DateTimeInterface
                && $job->delay->getTimestamp() === $publishedAt->timestamp,
        );
    }

    public function test_publishing_without_facebook_credentials_records_that_sharing_is_not_configured(): void
    {
        config([
            'services.facebook_page.page_id' => null,
            'services.facebook_page.page_access_token' => null,
        ]);
        Queue::fake([PublishArticleToFacebook::class]);
        $editor = User::create([
            'name' => 'Desk Editor',
            'email' => 'unconfigured-facebook-editor@example.test',
            'password' => Hash::make('A-long-test-password-2026!'),
            'role' => 'editor',
        ]);
        $category = Category::create(['name' => 'Malawi', 'slug' => 'malawi']);

        $this->actingAs($editor)->post(route('admin.articles.store'), [
            'title' => 'A story without Facebook configuration',
            'category_id' => $category->id,
            'excerpt' => 'This article can still be published on the website.',
            'body' => 'The website publication must not fail when Facebook credentials are missing.',
            'status' => 'published',
        ])->assertRedirect(route('admin.articles.index'));

        $article = Article::where('title', 'A story without Facebook configuration')->firstOrFail();
        $this->assertSame('not_configured', $article->facebook_post_status);
        $this->assertSame(
            'Set a Facebook Page ID and Page access token in the application environment to enable automatic sharing.',
            $article->facebook_post_error,
        );
        Queue::assertNotPushed(PublishArticleToFacebook::class);
    }

    public function test_editorial_dashboard_manages_live_coverage_and_ads(): void
    {
        Storage::fake('public');
        $editor = User::create([
            'name' => 'Desk Editor',
            'email' => 'live-editor@example.test',
            'password' => Hash::make('A-long-test-password-2026!'),
            'role' => 'editor',
        ]);
        $article = $this->publishedArticle();

        $this->actingAs($editor)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Control centre');

        $this->post(route('admin.ads.store'), [
            'title' => 'Community campaign',
            'target_url' => 'https://advertiser.example.test/campaign',
            'placement' => 'article',
            'image' => UploadedFile::fake()->image('campaign.jpg'),
            'active' => '1',
        ])->assertRedirect();

        $advertisement = Advertisement::firstOrFail();
        $this->assertTrue(Storage::disk('public')->exists($advertisement->image_path));
        $this->get(route('articles.show', $article->slug))
            ->assertOk()
            ->assertSee('Community campaign')
            ->assertSee('sponsored', false);

        $this->post(route('admin.live.store'), [
            'title' => 'National assembly updates',
            'summary' => 'Rolling newsroom updates from today’s sitting.',
        ])->assertRedirect();

        $event = LiveEvent::firstOrFail();
        $this->post(route('admin.live.updates.store', $event), [
            'body' => 'The session has started, according to the assembly clerk.',
        ])->assertRedirect();

        $this->get(route('live.show', $event->slug))
            ->assertOk()
            ->assertSee('The session has started');
        $this->get(route('live.updates', ['event' => $event->slug, 'after' => 0]))
            ->assertOk()
            ->assertJsonPath('updates.0.body', 'The session has started, according to the assembly clerk.');

        $this->patch(route('admin.live.close', $event))->assertRedirect();
        $this->get(route('live.show', $event->slug))->assertNotFound();
    }

    public function test_administrators_manage_staff_without_removing_the_last_admin(): void
    {
        $admin = User::create([
            'name' => 'Administrator One',
            'email' => 'admin-one@example.test',
            'password' => Hash::make('A-long-test-password-2026!'),
            'role' => 'admin',
        ]);
        $secondAdmin = User::create([
            'name' => 'Administrator Two',
            'email' => 'admin-two@example.test',
            'password' => Hash::make('A-long-test-password-2026!'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk();
        $this->post(route('admin.users.store'), [
            'name' => 'New Desk Editor',
            'email' => 'new-editor@example.test',
            'role' => 'editor',
            'password' => 'A-strong-editor-password-2026!',
            'password_confirmation' => 'A-strong-editor-password-2026!',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', ['email' => 'new-editor@example.test', 'role' => 'editor', 'is_active' => true]);
        $this->patch(route('admin.users.update', $secondAdmin), [
            'role' => 'editor',
            'is_active' => '0',
        ])->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $secondAdmin->id, 'role' => 'editor', 'is_active' => false]);

        $this->patch(route('admin.users.update', $admin), [
            'role' => 'editor',
            'is_active' => '1',
        ])->assertSessionHasErrors('user');
        $this->assertSame('admin', $admin->fresh()->role);
    }

    public function test_inactive_staff_cannot_sign_in(): void
    {
        User::create([
            'name' => 'Inactive Editor',
            'email' => 'inactive-editor@example.test',
            'password' => Hash::make('A-long-test-password-2026!'),
            'role' => 'editor',
            'is_active' => false,
        ]);

        $this->post(route('admin.login.store'), [
            'email' => 'inactive-editor@example.test',
            'password' => 'A-long-test-password-2026!',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_newsletter_requires_a_signed_confirmation_and_unsubscribe_link(): void
    {
        $this->post(route('newsletter.store'), ['email' => 'reader@example.test'])
            ->assertRedirect()
            ->assertSessionHas('status', 'Check your email to confirm your subscription.');

        $subscriber = Subscriber::where('email', 'reader@example.test')->firstOrFail();
        $this->assertNull($subscriber->verified_at);
        $this->assertSame(64, strlen($subscriber->verification_token_hash));
        $this->assertSame(64, strlen($subscriber->unsubscribe_token_hash));

        $verificationToken = Str::random(64);
        $subscriber->update(['verification_token_hash' => hash('sha256', $verificationToken)]);
        $confirmationUrl = URL::temporarySignedRoute(
            'newsletter.confirm',
            now()->addHour(),
            ['subscriber' => $subscriber->id, 'token' => $verificationToken],
        );

        $this->get($confirmationUrl)->assertOk()->assertSee('Your subscription is confirmed.');
        $this->assertNotNull($subscriber->fresh()->verified_at);

        $unsubscribeToken = Str::random(64);
        $subscriber->update(['unsubscribe_token_hash' => hash('sha256', $unsubscribeToken)]);
        $unsubscribeUrl = URL::signedRoute(
            'newsletter.unsubscribe',
            ['subscriber' => $subscriber->id, 'token' => $unsubscribeToken],
        );

        $this->get($unsubscribeUrl)->assertOk()->assertSee('You have been unsubscribed');
        $this->assertDatabaseMissing('subscribers', ['id' => $subscriber->id]);
    }

    public function test_story_likes_are_stored_without_an_ip_address(): void
    {
        $article = $this->publishedArticle();

        $response = $this->post(route('articles.like', $article->slug))->assertRedirect();

        $this->assertDatabaseHas('article_likes', ['article_id' => $article->id]);
        $this->assertNotNull($response->getCookie('news_visitor'));
        $this->assertFalse(Schema::hasColumn('article_likes', 'ip_address'));
    }

    public function test_contact_form_routes_general_advertising_and_news_submissions_to_the_right_desks(): void
    {
        Storage::fake('local');
        Mail::fake();
        $category = Category::create(['name' => 'Jobs & Internships', 'slug' => 'jobs-internships']);

        $this->get(route('contact'))
            ->assertOk()
            ->assertSee('info@newslane.africa')
            ->assertSee('sales@newslane.africa')
            ->assertSee('editor@newslane.africa')
            ->assertSee('News tip or original story submission')
            ->assertSee('rights_confirmation');

        $this->post(route('contact.store'), [
            'request_type' => 'general',
            'name' => 'Reader Example',
            'email' => 'reader@example.test',
            'subject' => 'A question for NEWSLANE',
            'message' => 'I have a question for the independent NEWSLANE team.',
        ])->assertRedirect(route('contact'));

        $general = ContactMessage::where('request_type', 'general')->firstOrFail();
        $this->assertSame('new', $general->status);

        $response = $this->from(route('contact'))->post(route('contact.store'), [
            'request_type' => 'news',
            'name' => 'Original Reporter',
            'email' => 'reporter@example.test',
            'subject' => 'An original public interest story',
            'news_headline' => 'A locally reported community story',
            'category_id' => $category->id,
            'source_attribution' => 'My original interview notes and a photo I took.',
            'message' => 'I witnessed and reported this original local story.',
            'rights_confirmation' => '1',
            'attachment' => UploadedFile::fake()->image('original-photo.jpg'),
        ])->assertRedirect(route('contact'));

        $submission = ContactMessage::where('request_type', 'news')->firstOrFail();
        $this->assertSame($category->id, $submission->category_id);
        Mail::assertSent(ContactSubmissionNotification::class, fn ($mail) => $mail->hasTo('info@newslane.africa'));
        Mail::assertSent(ContactSubmissionNotification::class, fn ($mail) => $mail->contact->is($submission) && $mail->hasTo('editor@newslane.africa'));
        $this->assertTrue(Storage::disk('local')->exists($submission->attachment_path));

        $this->post(route('contact.store'), [
            'request_type' => 'advertising',
            'name' => 'Local Advertiser',
            'email' => 'advertiser@example.test',
            'subject' => 'Advertising placement',
            'message' => 'We would like to discuss advertising with your commercial team.',
            'campaign_budget' => 'MWK 250,000',
            'campaign_start' => '2026-12-01',
            'campaign_end' => '2026-12-15',
        ])->assertRedirect(route('contact'));

        $this->assertDatabaseHas('contact_messages', ['request_type' => 'advertising', 'campaign_budget' => 'MWK 250,000']);
        Mail::assertSent(ContactSubmissionNotification::class, fn ($mail) => $mail->hasTo('sales@newslane.africa'));
    }

    public function test_news_submission_requires_rights_confirmation_and_news_details(): void
    {
        $this->from(route('contact'))->post(route('contact.store'), [
            'request_type' => 'news',
            'name' => 'Reporter',
            'email' => 'reporter@example.test',
            'subject' => 'A news tip',
            'message' => 'A news submission describing an original verified event.',
        ])->assertRedirect(route('contact'))->assertSessionHasErrors(['news_headline', 'category_id', 'source_attribution', 'rights_confirmation']);

        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_contact_attachments_are_private_and_restricted_to_administrators(): void
    {
        Storage::fake('local');
        $response = $this->post(route('contact.store'), [
            'request_type' => 'general',
            'name' => 'Reader Example',
            'email' => 'reader@example.test',
            'subject' => 'A question for NEWSLANE',
            'message' => 'I have a document I would like to share privately.',
            'attachment' => UploadedFile::fake()->createWithContent('private.pdf', "%PDF-1.7\nPrivate NEWSLANE submission\n"),
        ])->assertRedirect(route('contact'));
        $contact = ContactMessage::firstOrFail();
        $this->assertTrue(Storage::disk('local')->exists($contact->attachment_path));

        $this->get(route('admin.inquiries.attachment', $contact))->assertRedirect(route('login'));

        $admin = User::create([
            'name' => 'NEWSLANE Administrator',
            'email' => 'contact-admin@example.test',
            'password' => Hash::make('A-long-test-password-2026!'),
            'role' => 'admin',
        ]);
        $download = $this->actingAs($admin)->get(route('admin.inquiries.attachment', $contact));
        $download->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $download->assertHeader('content-disposition', 'attachment; filename='.basename($contact->attachment_path));
    }

    private function publishedArticle(?Category $category = null, array $overrides = []): Article
    {
        $category ??= Category::create(['name' => 'Malawi', 'slug' => 'malawi']);
        $number = Article::count() + 1;
        $author = User::create([
            'name' => 'NEWSLANE Desk',
            'email' => 'desk'.$number.'@example.test',
            'password' => Hash::make('A-long-test-password-2026!'),
            'role' => 'editor',
        ]);

        return Article::create(array_merge([
            'category_id' => $category->id,
            'user_id' => $author->id,
            'title' => 'Malawi newsroom test headline '.$number,
            'slug' => 'malawi-newsroom-test-headline-'.$number,
            'excerpt' => 'A test summary for the published story.',
            'body' => 'The published story body contains more than twenty characters.',
            'status' => 'published',
            'published_at' => now()->subMinute(),
        ], $overrides));
    }

    private function weatherForecastPayload(): array
    {
        $today = now()->toDateString();

        return array_map(fn (): array => [
            'current' => [
                'temperature_2m' => 26.4,
                'relative_humidity_2m' => 57,
                'apparent_temperature' => 27.1,
                'weather_code' => 2,
                'wind_speed_10m' => 8.5,
            ],
            'daily' => [
                'time' => [$today, now()->addDay()->toDateString(), now()->addDays(2)->toDateString()],
                'weather_code' => [2, 61, 0],
                'temperature_2m_max' => [29.5, 31, 30],
                'temperature_2m_min' => [19, 20.5, 18.3],
            ],
        ], config('weather.locations'));
    }

    private function clearWeatherCache(): void
    {
        Cache::forget(config('weather.open_meteo.cache_key'));
        Cache::forget(config('weather.open_meteo.last_success_cache_key'));
    }
}
