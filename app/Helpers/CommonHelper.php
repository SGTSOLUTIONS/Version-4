<?php

namespace App\Helpers;

class CommonHelper
{
    public static function uploadProfileImage($image, $path = 'profiles')
    {
        $destinationPath = public_path($path);

        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0777, true);
        }

        $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();

        $image->move($destinationPath, $imageName);

        return $path . '/' . $imageName;
    }

    public static function deleteProfileImage($path)
    {
        if ($path && file_exists(public_path($path))) {
            unlink(public_path($path));
            return true;
        }
        return false;
    }
}
