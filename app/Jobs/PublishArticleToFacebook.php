<?php

namespace App\Jobs;

use App\Models\Article;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class PublishArticleToFacebook implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 20;

    public int $uniqueFor = 86400;

    public function __construct(public int $articleId, public int $publicationTimestamp) {}

    public function handle(): void
    {
        $article = Article::findOrFail($this->articleId);

        if ($article->facebook_post_id) {
            return;
        }

        if (! $article->published_at || $article->published_at->timestamp !== $this->publicationTimestamp) {
            return;
        }

        if ($article->status !== 'published' || ! $article->published_at || $article->published_at->isFuture()) {
            $article->forceFill([
                'facebook_post_status' => 'skipped',
                'facebook_post_error' => 'The story was not publicly published when the Facebook job ran.',
            ])->save();

            return;
        }

        $pageId = config('services.facebook_page.page_id');
        $accessToken = config('services.facebook_page.page_access_token');
        $apiVersion = config('services.facebook_page.graph_api_version');

        if (! is_string($pageId) || ! ctype_digit($pageId)
            || ! is_string($accessToken) || $accessToken === ''
            || ! is_string($apiVersion) || ! preg_match('/^v[0-9]+\.[0-9]+$/D', $apiVersion)) {
            $this->markFailed($article, 'Facebook publishing is not configured with a valid Page ID, access token, and Graph API version.');

            return;
        }

        $applicationUrl = config('app.url');
        $host = is_string($applicationUrl) ? parse_url($applicationUrl, PHP_URL_HOST) : null;

        if (! is_string($host) || ! str_starts_with($applicationUrl, 'https://')
            || in_array(mb_strtolower($host), ['localhost', '127.0.0.1', '::1'], true)
            || str_ends_with(mb_strtolower($host), '.test')) {
            $this->markFailed($article, 'Set APP_URL to the public HTTPS website address before publishing stories to Facebook.');

            return;
        }

        $claimed = Article::query()
            ->whereKey($article->id)
            ->whereNull('facebook_post_id')
            ->where('facebook_post_status', 'pending')
            ->update(['facebook_post_status' => 'publishing']);

        if ($claimed !== 1) {
            return;
        }

        $url = rtrim($applicationUrl, '/').route('articles.show', $article->slug, false);
        $response = Http::asForm()
            ->connectTimeout(3)
            ->timeout(12)
            ->post("https://graph.facebook.com/{$apiVersion}/{$pageId}/feed", [
                'access_token' => $accessToken,
                'message' => $article->title."\n\n".$article->excerpt,
                'link' => $url,
            ]);

        if (! $response->successful()) {
            $this->markFailed($article, 'Facebook rejected the post (HTTP '.$response->status().'). Check the Page access token and permissions.');

            Log::warning('Facebook rejected a NEWSLANE article post.', [
                'article_id' => $article->id,
                'http_status' => $response->status(),
            ]);

            return;
        }

        $postId = $response->json('id');

        if (! is_string($postId) || $postId === '') {
            $this->markFailed($article, 'Facebook returned a successful response without a post ID. Check the Page before retrying.');

            Log::error('Facebook returned no post ID for a NEWSLANE article.', [
                'article_id' => $article->id,
            ]);

            return;
        }

        $article->forceFill([
            'facebook_post_status' => 'posted',
            'facebook_post_id' => $postId,
            'facebook_posted_at' => now(),
            'facebook_post_error' => null,
        ])->save();

        Log::info('NEWSLANE article was posted to Facebook.', [
            'article_id' => $article->id,
            'facebook_post_id' => $postId,
        ]);
    }

    public function uniqueId(): string
    {
        return $this->articleId.':'.$this->publicationTimestamp;
    }

    public function failed(?Throwable $exception): void
    {
        Article::whereKey($this->articleId)->whereNull('facebook_post_id')->update([
            'facebook_post_status' => 'failed',
            'facebook_post_error' => 'The Facebook publishing job failed. Check the Page before retrying to avoid duplicate posts.',
        ]);

        Log::error('Facebook article publishing job failed.', [
            'article_id' => $this->articleId,
            'exception' => $exception ? $exception::class : null,
        ]);
    }

    private function markFailed(Article $article, string $message): void
    {
        $article->forceFill([
            'facebook_post_status' => 'failed',
            'facebook_post_error' => $message,
        ])->save();
    }
}
