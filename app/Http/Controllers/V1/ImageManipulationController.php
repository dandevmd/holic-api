<?php

namespace App\Http\Controllers\V1;

use App\Models\Album;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\ImageManipulation;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image;
use App\Http\Requests\ResizeImageRequest;
use App\Http\Resources\V1\ImageManipulationResource;

class ImageManipulationController extends Controller
{
    /*
     * Display a listing of the resource.
     */
    public function index(Request $request, ImageManipulation $image)
    {
        return ImageManipulationResource::collection($image->paginate());
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, ImageManipulation $image)
    {
        return new ImageManipulationResource($image);
    }

    /**
     * Display the specified resource by filter
     */
    public function byAlbum(Request $request, Album $album)
    {
        $where = [
            'album_id' => $album->id,
            'user_id' => auth()->id() ?? null
        ];

        return ImageManipulationResource::collection(ImageManipulation::where($where)->get());
    }

    public function resize(ResizeImageRequest $request)
    {
        //get all fields
        $all = $request->all();
        // get image fiels
        $image = $all['image'];
        //delete image field from client provided data
        unset($all['image']);
        //prepare data to be saved
        $data = [
            'type' => ImageManipulation::TYPE_RESIZE,
            'data' => json_encode($all),
            'user_id' => null
        ];

        if (isset($all['album_id'])) {

            $data['album_id'] = $all['album_id'];
        }

        // createa dir images/random/img-resized.jpg
        $dir = 'images/' . Str::random() . '/';
        $absolutePath = public_path($dir);
        File::makeDirectory($absolutePath);

        if ($image instanceof UploadedFile) {
            $data['name'] = $image->getClientOriginalName();
            //transform testimg.jpg to testimg-resize.jpg
            $filename = pathinfo($data['name'], PATHINFO_FILENAME);
            $extension = $image->getClientOriginalExtension();
            $originalPath = $absolutePath . $data['name'];
            $image->move($absolutePath, $data['name']);
        } else {
            $data['name'] = pathinfo($image, PATHINFO_BASENAME);
            $filename = pathinfo($image, PATHINFO_FILENAME);
            $extension = pathinfo($image, PATHINFO_EXTENSION);
            $originalPath = $absolutePath . $data['name'];
            copy($image, $absolutePath . $data['name']);
        }

        $data['path'] = $dir . $data['name'];

        $w = $all['w'];
        $h = $all['h'] ?? false;

        list($width, $height, $image) = $this->getWidthAndHeight($w, $h, $originalPath);

        $resizedFilename = $filename . '-resized.' . $extension;
        $image->resize($width, $height)->save($absolutePath . $resizedFilename);

        $data['output_path'] = $dir . $resizedFilename;

        return new ImageManipulationResource(ImageManipulation::create($data));

    }



    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, ImageManipulation $image)
    {
        $folderName = explode('/', $image['path'])[1];
        $folderPath = public_path($folderName);

        File::deleteDirectory(public_path('/images/' . $folderName));
        $image->delete();

        return response()->json([
            'success' => true,
            'message' => 'Folder and files deleted successfully',
        ]);


    }


    protected function getWidthAndHeight($w, $h, $originalPath)
    {
        $image = Image::make($originalPath);
        $originalWidth = $image->width();
        $originalHeight = $image->height();

        if (str_ends_with($w, '%')) {
            $ratioW = (float) (str_replace('%', '', $w));
            $ratioH = $h ? (float) (str_replace('%', '', $h)) : $ratioW;
            $newWidth = $originalWidth * $ratioW / 100;
            $newHeight = $originalHeight * $ratioH / 100;
        } else {
            $newWidth = (float) $w;

            /**
             * $originalWidth  -  $newWidth
             * $originalHeight -  $newHeight
             * -----------------------------
             * $newHeight =  $originalHeight * $newWidth/$originalWidth
             */
            $newHeight = $h ? (float) $h : ($originalHeight * $newWidth / $originalWidth);
        }

        return [$newWidth, $newHeight, $image];
    }
}