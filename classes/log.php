<?php

class Log {

	private $handle;

	private $folder;
	private $archiveFolder;
	private $fileSize;
	private $path;

	public function __construct($filename) {
		date_default_timezone_set('Europe/Moscow');
		$this->folder = $_SERVER['DOCUMENT_ROOT'] . '/logs/';
		$this->archiveFolder = $_SERVER['DOCUMENT_ROOT'] . '/logs/archive/';
		$this->fileSize = 15000000;
		$this->path = $this->folder . $filename;
		$this->checkFile($filename);
		$isNew = !file_exists($this->path);
		$this->handle = @fopen($this->path, 'a');
		if ($this->handle === false) {
			$this->handle = null;
			error_log('Log: cannot open ' . $this->path . ' for writing');
			return;
		}
		// the logs folder is shared between apache (www-data) and CLI runs (often root),
		// so keep every log file appendable by both
		if ($isNew)
			@chmod($this->path, 0666);
	}

	public function write($message) {
		$line = date('Y-m-d G:i:s') . ' - ' . print_r($message, true) . "\n";
		if (!$this->handle) {
			error_log(rtrim($line));
			return;
		}
		fwrite($this->handle, $line);
	}


	public function clear() {
		if ($this->handle)
			ftruncate($this->handle, 0);
	}

	public function __destruct() {
		if ($this->handle)
			fclose($this->handle);
	}

	public function checkFile($filename) {
		$path = $this->folder . $filename;
		if (!is_file($path))
			return;
		$size = @filesize($path);
		if ($size !== false && $size > $this->fileSize)
			$this->archiveFile($filename);
	}

	public function archiveFile($filename) {
		$filenameSplit = explode('.', $filename);
		if (!is_dir($this->archiveFolder))
			if (!@mkdir($this->archiveFolder, 0777, true) && !is_dir($this->archiveFolder)) {
				error_log('Log: cannot create archive folder ' . $this->archiveFolder);
				return;
			}
		//rename($this->folder . $filename, $this->archiveFolder . $filenameSplit[0] . '-' . date('Ymd_H') . '.' . $filenameSplit[1]);
		if (!@rename($this->folder . $filename, $this->archiveFolder . $filename))
			error_log('Log: cannot archive ' . $this->folder . $filename);
	}
}
