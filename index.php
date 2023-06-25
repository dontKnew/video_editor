<?php
    require_once 'video.php';
    $video = new VideoEditor();
    $video->user_model = $video->cb_model;
    echo $video->noiseReduce("src/input/video/2_numbri.mp4", $speed=2);
