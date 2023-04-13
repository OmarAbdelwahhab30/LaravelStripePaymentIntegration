<?php

namespace App\Traits;


use Illuminate\Support\Facades\Storage;

trait FileUploaderTrait {

    /**
     * Handle an incoming File.
     *
     * @param $File or image that is to be validated
     *@param $ToWhichFolder you need to move the image or file
     * @return mixed
     */
    function ValidateFile($File,$ToWhichFolder)
    {
        if (!empty($File)) {
            $FileName = time() . "-" . $File->getClientOriginalName() . '.' . $File->extension();
            $Done = $File->move(public_path('storage/' . $ToWhichFolder), $FileName);
            if ($Done) {
                return $FileName;
            }
            return false;
        } else {
            return false;
        }
    }

    /*
     * $FILENAME => THE NAME OF THE FILE YOU WANT TO DELETE
     * $FromWhichFolder => the image folder(UserImages-BooksImages-BooksFiles)
     * unlink(string $filename, ?resource $context = null): bool
     * */
    function DeleteFile($Filename,$FromWhichFolder){
        if (unlink(public_path('storage/'.$FromWhichFolder.$Filename))){
            return true;
        }
        return false;
    }
}
