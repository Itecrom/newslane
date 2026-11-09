<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Advertisement;
use App\Models\Category;
use App\Models\Comment;
use App\Models\ContactMessage;
use App\Models\LiveEvent;
use App\Models\Subscriber;
use App\Models\User;
use App\Mail\ContactSubmissionNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Schema;
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
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Security-Policy');

        $this->get(route('category.show', 'politics'))
            ->assertOk()
            ->assertSee($article->title);
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
            ->assertSee($first->image_alt)
            ->assertSee($event->video_embed_url)
            ->assertHeader('Content-Security-Policy');

        $this->assertSame(3, substr_count($response->getContent(), 'data-carousel-slide'));
    }

    public function test_public_story_uses_root_slug_and_search_metadata(): void
    {
        $article = $this->publishedArticle();

        $this->get(route('articles.show', $article->slug))
            ->assertOk()
            ->assertSee('<meta property="og:title" content="'.$article->title.'">', false)
            ->assertSee(route('articles.show', $article->slug), false);

        $this->get(route('search', ['q' => 'Malawi']))
            ->assertOk()
            ->assertSee($article->title);

        $this->get(route('sitemap'))
            ->assertOk()
            ->assertSee(route('articles.show', $article->slug), false);
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

    public function test_editor_can_publish_and_reader_comments_are_moderated(): void
    {
        $editor = User::create([
            'name' => 'Desk Editor',
            'email' => 'editor@example.test',
            'password' => Hash::make('A-long-test-password-2026!'),
            'role' => 'editor',
        ]);
        $category = Category::create(['name' => 'Malawi', 'slug' => 'malawi']);

        $this->actingAs($editor)->post(route('admin.articles.store'), [
            'title' => 'A newsroom test story',
            'category_id' => $category->id,
            'excerpt' => 'A concise summary for the front page.',
            'body' => 'This is a sufficiently long newsroom test story.',
            'status' => 'published',
        ])->assertRedirect(route('admin.articles.index'));

        $article = Article::where('title', 'A newsroom test story')->firstOrFail();
        $this->assertSame('a-newsroom-test-story', $article->slug);
        $this->get(route('articles.show', $article->slug))->assertOk();

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
}
