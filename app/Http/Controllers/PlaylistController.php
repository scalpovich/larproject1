<?php

namespace App\Http\Controllers;

use App\Image;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Gate;
use App\Http\Requests;

use App\Playlist;

class PlaylistController extends Controller
{
    public function index()
    {
        if (Gate::check('is-admin')) {
            $playlists = Playlist::orderBy('updated_at', 'desc')->get();
        } else {
            $playlists = Playlist::where('user_id', auth()->user()->id)->orderBy('updated_at', 'desc')->get();
        }

        return view('playlists.index', [
            'myjs' => ['jquery.dynatable.js'],
            'mycss' => ['jquery.dynatable.css'],
            'playlists' => $playlists,
            'cp' => true
        ]);
    }

    public function create()
    {
        return view('playlists.create', [
            'myjs' => ['playlists/create.js'],
            'cp' => true
        ]);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'playlist_title' => 'required|unique:playlists,playlist_title',
            'cate_id' => 'required|integer',
            'playlist_songs' => 'required',
            'playlist_info' => 'string',
            'playlist_img' => 'required|mimetypes:image/jpeg,image/png'
        ]);

        $playlist = new Playlist;
        $playlist->playlist_title = $request->playlist_title;
        $playlist->playlist_info = $request->playlist_info;
        $playlist->cate_id = $request->cate_id;
        $playlist->user_id = $request->user()->id;
        $playlist->save();

        // Move upload image to appriciate location
        $playlist_img = $request->file('playlist_img');
        $playlist_img->move(base_path('public/uploads/imgs'), $playlist_img->getClientOriginalName());

        // After uploading was done. Do insert info to DB
        $link = asset('uploads/imgs/' . $playlist_img->getClientOriginalName());

        $image = new Image;
        $image->image_path = $link;
        $image->imageable_id = $playlist->id;
        $image->imageable_type = Playlist::class;
        $image->save();

        $songs = explode(',', $request->playlist_songs);

        /*Here i have 2 opts:
        first: $song->artists()->attach($artist)
        second: $artist->songs()->attach($song->id);*/

        foreach ($songs as $song) {
            if ($song != '') $playlist->songs()->attach($song);
        }

        return back()->with('succeeds', 'Tao danh sach nhac thanh cong!');
    }

    public function edit(Playlist $playlist)
    {
        if (!Gate::check('is-admin') && auth()->user()->id != $playlist->user_id) {
            session()->flash('my_errors', 'Bạn không có quyền truy cập Danh sách nhạc của người khác!');
            return back();
        }

        return view('playlists.edit', [
            'myjs' => ['playlists/create.js'],
            'playlist' => $playlist,
            'cp' => true
        ]);
    }

    public function update(Request $request, Playlist $playlist)
    {
        if (!Gate::check('is-admin') && auth()->user()->id != $playlist->user_id) {
            session()->flash('my_errors', 'Bạn không có quyền truy cập Danh sách nhạc của người khác!');
            return back();
        }
        $this->validate($request, [
            'playlist_title' => 'required|unique:playlists,playlist_title,' . $playlist->id,
            'cate_id' => 'required|integer',
            'playlist_songs' => 'required',
            'playlist_info' => 'string',
            'playlist_img' => 'mimetypes:image/jpeg,image/png'
        ]);

        $playlist->playlist_info = $request->playlist_info;
        $playlist->playlist_title = $request->playlist_title;
        $playlist->cate_id = $request->cate_id;

        $playlist->save();

        $playlist_img = $request->file('playlist_img');
        if ($playlist_img) {
            // Remove old img
            preg_match("/imgs\/(.*)/", $playlist->image->image_path, $temp);
            @unlink(base_path('public/uploads/imgs/' . $temp[1]));
            $playlist->image()->delete();


            // Move upload image to appriciate location
            $playlist_img->move(base_path('public/uploads/imgs'), $playlist_img->getClientOriginalName());

            // After uploading was done. Do insert info to DB
            $link = asset('uploads/imgs/' . $playlist_img->getClientOriginalName());

            $image = new Image;
            $image->image_path = $link;
            $image->imageable_id = $playlist->id;
            $image->imageable_type = Playlist::class;
            $image->save();
        }

        // Process playlist_songs
        // First we remove old song_artists
        $playlist->songs()->detach();
        $songs = explode(',', $request->playlist_songs);

        /*Here i have 2 opts:
        first: $song->artists()->attach($artist)
        second: $artist->songs()->attach($song->id);*/

        foreach ($songs as $song) {
            if ($song != '') $playlist->songs()->attach($song);
        }

        return back()->with('succeeds', 'Cap nhat danh sach nhac thanh cong!');
    }

    public function delete(Playlist $playlist, Request $request)
    {
        if (!Gate::check('is-admin') && auth()->user()->id != $playlist->user_id) {
            session()->flash('my_errors', 'Bạn không có quyền truy cập Danh sách nhạc của người khác!');
            return back();
        }

        $playlist->songs()->detach();
        $playlist->delete();

        $request->session()->flash('succeeds', 'Your done!');
        return back();
    }

    public function show($playlist)
    {
        // Is number and > 0 => user playlist
        if (is_numeric($playlist) and $playlist>0) {
            $playlist = Playlist::find($playlist);
            SessionController::increase_view_playlist($playlist);
            return view('playlists.show', [
                'myjs' => ['player.js', 'playlists/show.js'],
                'playlist' => $playlist,
                'api_url' => url("api/get-songs-in-playlist/$playlist->id"),
            ]);

        } else { // Else = 0 or temp-playlist -> templaylist

            return view('playlists.guest_show', [
                'myjs' => ['player.js', 'playlists/guest_show.js'],
                'api_url' => url("api/get-songs-in-playlist/0"),
            ]);
        }

    }
}
