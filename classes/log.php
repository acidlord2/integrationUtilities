<?php

class Log {

	private $handle;

	private $folder;
	private $archiveFolder;
	private $fileSize;

	public function __construct($filename) {
		date_default_timezone_set('Europe/Moscow');
		$this->folder = $_SERVER['DOCUMENT_ROOT'] . '/logs/';
		$this->archiveFolder = $_SERVER['DOCUMENT_ROOT'] . '/logs/archive/';
		$this->fileSize = 15000000;
		$this->checkFile($filename);
		$this->handle = fopen($this->folder . $filename, 'a');
	}

	public function write($message) {
		fwrite($this->handle, date('Y-m-d G:i:s') . ' - ' . print_r($message, true) . "\n");
	}


	public function clear() {
		ftruncate($this->handle, 0);
	}

	public function __destruct() {
		fclose($this->handle);
	}

	public function checkFile($filename) {
		if (file_exists($this->folder . $filename))
			if (filesize($this->folder . $filename) > $this->fileSize)
				$this->archiveFile($filename);
	}

	public function archiveFile($filename) {
		$filenameSplit = explode  ('.', $filename);
		if (!is_dir ($this->archiveFolder))
			mkdir ($this->archiveFolder);
		//rename($this->folder . $filename, $this->archiveFolder . $filenameSplit[0] . '-' . date('Ymd_H') . '.' . $filenameSplit[1]);
		rename($this->folder . $filename, $this->archiveFolder . $filename);
	}
}

?>