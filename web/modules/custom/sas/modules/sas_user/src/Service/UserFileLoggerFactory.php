<?php

namespace Drupal\sas_user\Service;

use Drupal\Core\File\FileSystemInterface;

/**
 * Class FileLoggerFactory.
 *
 * CSV file logger factory service.
 *
 * @package Drupal\sas_user\Service
 */
class UserFileLoggerFactory implements UserFileLoggerFactoryInterface {

  // Directory.
  const SAS_FILE_DIR = 'private://sas_users';

  /**
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected FileSystemInterface $fileSystem;

  /**
   * UserFileLoggerFactory constructor.
   */
  public function __construct(FileSystemInterface $file_system) {
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritDoc}
   */
  protected function prepareDirectory(): bool {
    $dirname = self::SAS_FILE_DIR;
    return $this->fileSystem->prepareDirectory($dirname, FileSystemInterface::CREATE_DIRECTORY);
  }

  /**
   * {@inheritDoc}
   */
  public function buildCsvFile(array $data, string $filename) {
    /** @var \Drupal\sas_export_cnam\FileExporterInterface $file */
    $filepath = self::SAS_FILE_DIR . DIRECTORY_SEPARATOR . $filename;

    // Open stream.
    $fh = fopen($filepath, 'w');

    // Add header.
    fputcsv($fh, array_keys($data[0]), ";");

    foreach ($data as $row) {
      fputcsv($fh, $row, ';');
    }

    fclose($fh);
  }

}
