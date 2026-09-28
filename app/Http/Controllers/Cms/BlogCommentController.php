<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Services\Settings\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BlogCommentController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function store(Request $request, BlogPost $post): RedirectResponse
    {
        abort_unless($this->settings->get('blog_comments_enabled', false), 404);

        $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            'parent_id' => ['nullable', 'exists:blog_comments,id'],
        ]);

        $post->comments()->create([
            'user_id' => $request->user()->id,
            'parent_id' => $request->parent_id,
            'body' => $request->body,
            'status' => 'approved',
        ]);

        activity()->causedBy($request->user())->performedOn($post)->log('Comment posted on blog post');

        return back()->with('status', 'comment-posted');
    }
}
