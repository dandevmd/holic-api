<?php

namespace App\Http\Controllers\V1;

use App\Models\Album;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreAlbumRequest;
use App\Http\Resources\V1\AlbumResource;
use App\Http\Requests\UpdateAlbumRequest;

class AlbumController extends Controller
{

    public function index(Request $request)
    {
        $user = Auth::user();
        echo "<pre>";
        var_dump(
            $user
        );
        echo "</pre>";
        exit;
        return AlbumResource::collection(Album::where('user_id', $request->user()->id)->paginate());
    }
    public function store(StoreAlbumRequest $request)
    {
        //validate 
        $validated = $request->validated();

        if (!$validated) {
            return response()->json([
                'success' => false,
                'message' => 'Album not created'
            ]);
        }
        //create
        $album = Album::create($validated);
        return response()->json([
            'success' => true,
            'message' => 'Album created successfully',
            //wrap the album in a resource
            'album' => AlbumResource::make($album)
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Album $album)
    {
        $albums = Album::find($album->id);
        return !$album ?
            response()->json([
                'success' => false,
                'message' => 'Album not found'
            ])
            : response()->json([
                'success' => true,
                'message' => 'Alles good',
                'album' => new AlbumResource($albums)
            ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAlbumRequest $request, Album $album)
    {
        $album->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Album updated successfully',
            'album' => new AlbumResource($album)
        ]);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Album $album)
    {
        $album->delete();

        return response()->json([
            'success' => true,
            'message' => 'Album deleted successfully',
            'album' => new AlbumResource($album)
        ]);
    }
}