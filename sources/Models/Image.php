<?php
namespace CameraLife\Models;

/**
 * Class Image allows loading and modifying images
 *
 * @author    William Entriken <cameralife@phor.net>
 * @access    public
 * @version
 * @copyright 2001-2015 William Entriken
 */
class Image
{
    public $width;
    public $height;
    public $extension;
    private $size;
    private $originalImage;
    private $scaledImages;

    public function __construct($filename)
    {
        if (!file_exists($filename)) {
            throw new \Exception('Trying to process non-existant image: ' . $filename);
        }
        if (!is_readable($filename)) {
            throw new \Exception('Image not readable: ' . $filename);
        }
        
        $pathParts = pathinfo($filename);
        $this->extension = strtolower($pathParts['extension']);
        
        $imageData = file_get_contents($filename);
        $this->originalImage = \imagecreatefromstring($imageData);
        $this->width = imagesx($this->originalImage);
        $this->height = imagesy($this->originalImage);
        $this->size = sqrt($this->width * $this->width + $this->height * $this->height);
    }

    public function destroy()
    {
        imagedestroy($this->originalImage);
        if (isset($this->scaled_image) && count($this->scaled_image) > 0) {
            foreach ($this->scaled_image as $image) {
                imagedestroy($image);
            }
        }
    }

    public function check()
    {
        return !empty($this->originalImage);
    }

    public function getSize()
    {
        return array($this->width, $this->height);
    }

    /**
     * Resizes this image and saves to this new file
     * If you have already resized the image to something larger than you want
     * you can rescale it. This is much faster than scaling the original, larger, image again.
     *
     * @param  string $filename
     * @param  int    $newSize  diagonal size
     * @param  int    $quality
     * @return array|void dimensions new width and height
     */
    public function resize($filename, $newSize, $quality = 91)
    {
        $baseImage = $this->originalImage;
        $baseSize = $this->size;

        if (count($this->scaledImages)) {
            foreach ($this->scaledImages as $size => $image) {
                if ($size < $baseSize && $size > $newSize) {
                    $baseImage = $image;
                    $baseSize = $size;
                }
            }
        }
        $baseWidth = $this->width * $baseSize / $this->size;
        $baseHeight = $this->height * $baseSize / $this->size;
        $newWidth = $this->width * $newSize / $this->size;
        $newHeight = $this->height * $newSize / $this->size;
        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        if (empty($newImage)) {
            throw new \Exception("Can't make new image");
        }

        imagecopyresampled(
            $newImage,
            $baseImage,
            0,
            0,
            0,
            0,
            $newWidth,
            $newHeight,
            $baseWidth,
            $baseHeight
        );

        if ($this->extension == 'jpeg' || $this->extension == 'jpg') {
            $result = imagejpeg($newImage, $filename, $quality);
            if (!$result) {
                throw new \Exception("Could not write the file $filename is the directory writable?");
            }
        } elseif ($this->extension == 'png') {
            $result = imagepng($newImage, $filename, 9 - $quality / 11);
            if (!$result) {
                throw new \Exception("Could not write the file $filename is the directory writable?");
            }
        } elseif ($this->extension == 'gif') {
            $result = imagegif($newImage, $filename, 9 - $quality / 11);
            if (!$result) {
                throw new \Exception("Could not write the file $filename is the directory writable?");
            }
        }

        $this->scaled_image[$newSize] = $newImage;

        return array($newWidth, $newHeight);
    }

    /**rotate the image a 90, 180 or 270 degrees clockwise
     */
    public function rotate($degrees)
    {
        ini_set('max_execution_time', 100);

        if (function_exists('imagerotate')) {
            $rotated = imagerotate($this->originalImage, -$degrees, 0);
        } else {
            $rotated = $this->imagerotateRightAngle($this->originalImage, $degrees);
        }
        if ($degrees == 90 || $degrees == 270) {
            $oldwidth = $this->width;
            $this->width = $this->height;
            $this->height = $oldwidth;
        }
        $this->destroy();
        $this->originalImage = $rotated;
    }

    /**Saves the image at orginal resolution
     */
    public function save($filename, $quality = 91)
    {
        if ($this->extension == 'jpeg' || $this->extension == 'jpg' || $this->extension == '') {
            imagejpeg($this->originalImage, $filename, $quality)
                or $cameralife->Error("Could not write the file $filename is the directory writable?");
        } elseif ($this->extension == 'png') {
            imagepng($this->originalImage, $filename, 9 - $quality / 11)
                or $cameralife->Error("Could not write the file $filename is the directory writable?");
        }
    }

    /**
     * @var $srcX dimension of source image
     * @var $srcY dimension of source image
     * @return resource GD output image
     */
    private function imagerotateRightAngle($imgSrc, $angle)
    {
        // dimenstion of source image
        $srcX = imagesx($imgSrc);
        $srcY = imagesy($imgSrc);
        if ($angle == 90 || $angle == 270) {
            $imgDest = imagecreatetruecolor($srcY, $srcX);
        } else {
            $imgDest = imagecreatetruecolor($srcX, $srcY);
        }

        if ($angle == 90) {
            for ($x = 0; $x < $srcX; $x++) {
                for ($y = 0; $y < $srcY; $y++) {
                    imagecopy($imgDest, $imgSrc, $srcY - $y - 1, $x, $x, $y, 1, 1);
                }
            }
        } elseif ($angle == 270) {
            for ($x = 0; $x < $srcX; $x++) {
                for ($y = 0; $y < $srcY; $y++) {
                    imagecopy($imgDest, $imgSrc, $y, $srcX - $x - 1, $x, $y, 1, 1);
                }
            }
        } elseif ($angle == 180) {
            for ($x = 0; $x < $srcX; $x++) {
                for ($y = 0; $y < $srcY; $y++) {
                        imagecopy($imgDest, $imgSrc, $srcX - $x - 1, $srcY - $y - 1, $x, $y, 1, 1);
                }
            }
        }

        return ($imgDest);
    }
}
