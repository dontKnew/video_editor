<?php

class VideoEditor {

    protected $ffmpegPath;
    public $user_model;
    public $command = array();
    public $bd_model;
    public $cb_model;
    public $sh_model;
    public $input_path;
    public $output_path;
    private $video_format = [
        'mp4'=> 'MP4 (MPEG-4 Part 14)',
        'avi'=>'AVI (Audio Video Interleave)',
        'mkv'=>'MKV (Matroska)',
        'mov'=>'MOV (QuickTime)',
        'wmv'=>'WMV (Windows Media Video)',
        'flv'=>'FLV (Flash Video)',
        'mpeg'=>'MPEG (Moving Picture Experts Group)',
        'webm'=>'WebM (WebM Project)',
        '3gp'=>'3GP (3rd Generation Partnership Project)',
        'ASF'=>'ASF (Advanced Systems Format)'
    ];
    private $audio_format = [
        'mp3'  => 'MP3 (MPEG audio layer 3)',
        'wav'  => 'WAV (Waveform Audio File Format)',
        'aac'  => 'AAC (Advanced Audio Coding)',
        'ogg'  => 'OGG (Ogg Vorbis)',
        'flac' => 'FLAC (Free Lossless Audio Codec)',
        'wma'  => 'WMA (Windows Media Audio)',
        'm4a'  => 'M4A (MPEG-4 Audio Layer)',
        'opus' => 'Opus (Opus Interactive Audio Codec)',
        'aiff' => 'AIFF (Audio Interchange File Format)',
        'amr'  => 'AMR (Adaptive Multi-Rate Audio Codec)'
    ];

    public function __construct() {
        $this->ffmpegPath =  "library/ffmpeg.exe";
        $this->mp_model = "library/rnnoise-models/mp/mp.rnnn"; // bad
        $this->sh_model = "library/rnnoise-models/sh/sh.rnnn"; // good
        $this->bd_model = "library/rnnoise-models/bd/bd.rnnn"; // good
        $this->lq_model = "library/rnnoise-models/lq/lq.rnnn"; // low good
        $this->cb_model = "library/rnnoise-models/cb/cb.rnnn";
        $this->input_path = "src/input";
        $this->output_path =  "../api/src/output";
    }

    public function getAudio($videoPath) {
        $outputPath = $this->output_path . "/audio/_" . basename($videoPath, '.mp4') . "_" . time() . ".aac";
        $command =  escapeshellarg($this->ffmpegPath) . ' -i ' . escapeshellarg($videoPath) . ' -vn -acodec copy ' . escapeshellarg($outputPath);
        $this->executeCommand($command);
        return $outputPath;
    }

    public function noiseReduce($videoPath) {
        $audio_path = $this->getAudio($videoPath);
        $modelPath = $this->user_model;
        if (empty($modelPath)) {
            $modelPath = $this->mp_model;
        }
        $outputPath = $this->output_path . "/audio/noise_reduce_" . basename($videoPath) . "_" . time() . ".mp3";
        $command = escapeshellarg($this->ffmpegPath) . " -i " . escapeshellarg($audio_path) . " -af arnndn=m=" . escapeshellarg($modelPath) . " " . escapeshellarg($outputPath);
        $this->executeCommand($command);
        unlink($audio_path);
        return $outputPath;
    }

    /*
      Ex :  $video->trim("src/input/video/2_numbri.mp4", "00:00:00", "00:02:50"); // 2 min 50 sec
    */
    public function trim($videoPath, $startTime, $endTime) {
        $outputPath = $this->output_path . "/video/trim_" . basename($videoPath) . "_" . time() . ".mp4";
        $command = escapeshellarg($this->ffmpegPath) . " -i " . escapeshellarg($videoPath) . " -ss " . escapeshellarg($startTime) . " -to " . escapeshellarg($endTime) . " -c copy " . escapeshellarg($outputPath);
        $this->executeCommand($command);
        return $outputPath;
    }

    public function removeAudio($videoPath) {
        $outputPath = $this->output_path . "/video/removed_audio_" . basename($videoPath) . "_" . time() . ".mp4";
        $command = escapeshellarg($this->ffmpegPath) . " -i " . escapeshellarg($videoPath) . " -c copy -an " . escapeshellarg($outputPath);
        $this->executeCommand($command);
        return $outputPath;
    }

    public function mergeTwoVideos($videoPath, $video2Path) {
        $outputPath = $this->output_path . "/video/" . basename($videoPath) . "___" . basename($videoPath) . "_" . time() . ".mp4";
        $videoPath = "file '" . $videoPath . "'\nfile '" . $video2Path . "'";
        $command = escapeshellarg($this->ffmpegPath) . " -f concat -safe 0 -i " . escapeshellarg($videoPath) . " -c copy " . escapeshellarg($outputPath);
        $this->executeCommand($command);
        return $outputPath;
    }

    public function addAudio($videoPath, $audioPath, $startTime = null) {
        $outputPath = $this->output_path . "/video/add_audio_" . basename($videoPath) . "_" . time() . ".mp4";
        $command = escapeshellarg($this->ffmpegPath) . " -i " . escapeshellarg($videoPath) . " -i " . escapeshellarg($audioPath) . " -c:v copy -c:a aac";
        if ($startTime !== null) {
            $command .= " -ss " . escapeshellarg($startTime);
        }
        $command .= " -map 0:v:0 -map 1:a:0 " . escapeshellarg($outputPath);
        $this->executeCommand($command);
        return $outputPath;
    }

    public function changeVideoFormat($videoPath,  $outputFormat) {
        $outputFormat = strtolower($outputFormat);
        if(!$this->isExistsFormat($outputFormat)) {
            throw new Exception('Output format not exists');
        }
        $outputPath = $this->output_path . "/video/change_format" . basename($videoPath) . "_" . time() . "." . $outputFormat;
        $command = escapeshellarg($this->ffmpegPath) . " -i " . escapeshellarg($videoPath) . " -codec copy " . escapeshellarg($outputPath);
        $this->executeCommand($command);
        return $outputPath;
    }

    public function reduceSize($videoPath) {
        $outputPath = $this->output_path . "/video/reduce_size_" . time() . ".mp4";
        $command = escapeshellarg($this->ffmpegPath) . " -i " . escapeshellarg($videoPath) . " -c:v libx265 -c:a copy  " . escapeshellarg($outputPath);
        $this->executeCommand($command);
        return $outputPath;
    }
    public function increaseSpeed($videoPath, $speed = 1, $startTime = null, $endTime = null) {
        $outputPath = $this->output_path . "/video/increase_speed" . time() . ".mp4";
        $filter = "setpts=" . (1 / $speed) . "*PTS";
        //$filter = "setpts=" . (1 / $speed) . "*PTS, atempo=" . $speed;
        if ($startTime !== null && $endTime !== null) {
            $command = escapeshellarg($this->ffmpegPath) . " -i " . escapeshellarg($videoPath) . " -filter:v \"" . $filter . "\" -c:a copy -ss " . $startTime . " -to " . $endTime . " " . escapeshellarg($outputPath);
        } else {
            $command = escapeshellarg($this->ffmpegPath) . " -i " . escapeshellarg($videoPath) . " -filter:v " . $filter . " -c:a copy " . escapeshellarg($outputPath);
        }
        $this->executeCommand($command);
    }

    public function executeCommand($command) {
        $this->command[] = $command;
        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            throw new Exception('FFmpeg command  : '.$command );
        }
    }


    private function isExistsFormat($format) {
        $totalArr = array_merge($this->video_format, $this->audio_format);
        if(array_key_exists($format, $totalArr)) {
            return true;
        }
        return false;
    }
}