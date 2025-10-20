<?php

namespace Classes\Common;

class Log {

	private $handle;

	private $folder;
	private $archiveFolder;
	private $fileSize;

	public function __construct($filename) {
		$docroot = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 2);
		date_default_timezone_set('Europe/Moscow');
		$this->folder = $docroot . '/logs/';
		$this->archiveFolder = $docroot . '/logs/archive/';
		$this->fileSize = 15000000;
		$this->checkFile($filename);
		
		$logFile = $this->folder . $filename;
		$fileExisted = file_exists($logFile);
		
		// Try to open the file, handle permission issues
		$this->handle = @fopen($logFile, 'a');
		
		// Set proper permissions if file was just created or opened successfully
		if ($this->handle && !$fileExisted) {
			// File was just created, set permissions so both www-data and root can write
			@chmod($logFile, 0666); // Read/write for owner, group, and others
		}
	}

	public function write($message) {
		if ($this->handle) {
			fwrite($this->handle, date('Y-m-d G:i:s') . ' - ' . print_r($message, true) . "\n");
		} else {
			// Fallback: try to write to error log if file handle is not available
			error_log("LOG_FALLBACK: " . date('Y-m-d G:i:s') . ' - ' . print_r($message, true));
		}
	}

	public function clear() {
		ftruncate($this->handle, 0);
	}

	public function __destruct() {
		if ($this->handle && is_resource($this->handle)) {
			fclose($this->handle);
		}
	}

	public function checkFile($filename) {
		if (file_exists($this->folder . $filename)) {
			if (filesize($this->folder . $filename) > $this->fileSize) {
				// Close current handle before archiving
				if ($this->handle) {
					fclose($this->handle);
					$this->handle = null;
				}
				
				$this->archiveFile($filename);
				
				// Reopen the file (which will be a new empty file now)
				$logFile = $this->folder . $filename;
				$this->handle = @fopen($logFile, 'a');
				if ($this->handle) {
					@chmod($logFile, 0666);
				}
			}
		}
	}

	public function archiveFile($filename) {
		if (!is_dir ($this->archiveFolder)) {
			mkdir ($this->archiveFolder, 0755, true); // Create directory recursively with proper permissions
		}
		
		// Use the same filename in archive (replaces previous archive)
		$archivedFile = $this->archiveFolder . $filename;
		
		// Move the file to archive
		if (rename($this->folder . $filename, $archivedFile)) {
			@chmod($archivedFile, 0666); // Set proper permissions on archived file
			
			// Log the archiving action (to the new file that will be created)
			if ($this->handle) {
				fwrite($this->handle, date('Y-m-d G:i:s') . ' - LOG FILE ARCHIVED: ' . $filename . PHP_EOL);
			}
		} else {
			// If rename failed, try to log the error
			error_log("Failed to archive log file: $filename to $archivedFile");
		}
	}
}
?>