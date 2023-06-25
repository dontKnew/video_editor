### Video Editor Class for PHP Script with FFmpeg
This is a PHP class for video editing using FFmpeg. It provides various video editing features such as noise reduction, video merging, video resizing, and more. FFmpeg is a powerful multimedia framework that allows you to manipulate videos and perform various operations.

## Features :
- Resize videos to specific dimensions
- Extract audio from videos
- Increase or decrease video speed
- Reduce video file size
- Apply noise reduction to videos 
- get audio from video 
-  etc..

## Requirements
- FFmpeg: Download FFmpeg from https://ffmpeg.org/download.html for Windows. Place the ffmpeg.exe file in the library folder.


## Example : 
```php
    require_once 'video.php';
    $video = new VideoEditor();
    
    $video->user_model = $video->cb_model;
    $output = $video->noiseReduce("src/input/video/2_numbri.mp4");
    echo $output;
```

## Notes :
Make sure to set the correct path to the ffmpeg.exe file in the library folder within the VideoEditor class.
Check the src/output folder for the output files generated by the video editing operations.
Feel free to explore the video.php file for more available methods and their usage.

Disclaimer: This class assumes basic knowledge of PHP and FFmpeg. Make sure to handle input files and user data securely to prevent any vulnerabilities or misuse.

- Author Name: Sajid Ali 
- Email: sajid.phpmaster@gmail.com
- Youtube Channel : https://www.youtube.com/@phpmaster