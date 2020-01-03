<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Services\PostService;
use Services\UserService;
use Services\TagService;
use Services\ContributeService;
use Services\CommentService;
use Services\CommunityService;
use Requests\SettingUpdateRequest;

class FrontendController extends Controller
{
	protected $postService;
	protected $userService;
	protected $contributeService;
	protected $commentService;
	protected $communityService;
	protected $tagService;

	public function __construct(
		PostService $postService, 
		UserService $userService, 
		ContributeService $contributeService, 
		CommentService $commentService, 
		CommunityService $communityService,
		TagService $tagService
	)
	{
		$this->postService = $postService;
		$this->userService = $userService;
		$this->contributeService = $contributeService;
		$this->commentService = $commentService;
		$this->communityService = $communityService;
		$this->tagService = $tagService;
	}

	public function index(Request $request, $tag = false)
	{
		$posts = $this->postService->content(10, $request->all() + ['tag' => $tag]);

		if(!$posts)
			return abort(404);

		return view('welcome', compact('posts', 'tag'));
	}

	public function community()
	{
		$communities = $this->communityService->paginate();

		return view('community', compact('communities'));
	}

	public function discover(Request $request, $tag=false)
	{
		$posts = $this->postService->discover(10, $request->all() + ['tag' => $tag]);

		if(!$posts)
			return abort(404);

		return view('discover', compact('posts'));
	}

	public function about()
	{
		return view('about');
	}

	public function contact()
	{
		return view('contact');
	}

	public function single($slug, Request $request)
	{
		$post = $this->postService->findBySlug($slug, true);

		if(!$post)
		{
			$user = $this->userService->findByUsername($slug);

			if(!$user) return abort(404);

			$posts = $user->posts()->paginate(10);

			return view('profile', compact('user', 'posts'));
		}

		if($request->ajax) {
			return response()->json(['data' => $post]);
		}

		return view('single', compact('post'));
	}

	public function profileLoves($slug, Request $request)
	{
		$user = $this->userService->findByUsername($slug);

		$posts = $this->postService->myLovesBySlug($user, $request);

		if($request->ajax) {
			return response()->json($posts);
		}

	return view('loves', compact('posts', 'user'));
	}

	public function profileSaves(Request $request)
	{
		$user = auth()->user();

		$posts = $this->postService->mySavesBySlug($user, $request);

		if($request->ajax) {
			return response()->json($posts);
		}

		return view('saves', compact('posts', 'user'));
	}

	public function discuss($slug)
	{
		$user = $this->userService->findByUsername($slug);
		$comments = $this->commentService->mine($user->id);

		return view('discuss', compact('comments', 'user'));
	}

	public function setting()
	{
		$user = auth()->user();

		return view('setting', compact('user'));
	}

	public function settingUpdate(SettingUpdateRequest $request)
	{
		$setting = $this->userService->setting($request);

		if(!$setting)
			return redirect()->back()->withErrors();

		flash()->success('User setting saved successfully');

		return redirect()->back();
	}

	public function contributes($slug)
	{
		$user = $this->userService->findByUsername($slug);
		$contributes = $this->contributeService->byUser($slug);

		return view('contributes', compact('contributes', 'user'));
	}

	/**
	 * Search Page
	 * @return view
	 */
	public function search(Request $request)
	{
		$types = search_types();
		$type = $request->type ?? 'post';
		$search = [['name' => 'Search', 'search' => true]];
		$tags = $this->tagService->popular([5, 5]);

		$pluck = $tags->pluck('name')->all();
		$tag = $request->tag;
		if($tag && !in_array($tag, $pluck))
			$tags = array_merge([['name' => $tag]], $tags->toArray());
		else
			$tags = $tags->toArray();

		$tags = array_merge($search, $tags);

		return view('search', compact('types', 'type', 'tags', 'tag'));
	}

	public function deletePost($slug)
	{
		$post = $this->postService->findBySlug($slug);

		if(!$post->is_mine)
			return abort(404);

		$post_card = $post->post_card->toArray();

		return view('delete_post', compact('post', 'post_card'));
	}
}
