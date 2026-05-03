<?php

// declare(strict_types=1);

namespace OCA\Rolls\Service;

use Exception;
use OCA\Rolls\Db\Roll;
use OCA\Rolls\Db\RollsDb;
use OCA\Rolls\Entity\RollListItem;
use OCA\Rolls\Utils\Funcs;
use OCP\Files\IRootFolder;
use OCP\IUserSession;
use OCP\Share\IManager;
use Symfony\Component\Uid\Uuid;

/**
 * @psalm-suppress UnusedClass
 */
class RollService
{
	private IUserSession $session;
	private IRootFolder $storage;
	private IManager $shareManager;
	protected RollsDb $db;

	public function __construct(RollsDb $db, IUserSession $session, IRootFolder $rootFolder, IManager $shareManager)
	{
		$this->storage = $rootFolder;
		$this->db = $db;
		$this->session = $session;
		$this->shareManager = $shareManager;
	}


	public function createRoll($requestFile, $ext, $thumbFile, $tbExt, $requestText): Roll
	{
		$user = $this->session->getUser();
		$userFolder = $this->storage->getUserFolder($user->getUID());

		// Todo: should be a variable
		$videoFolderName = Funcs::joinPaths($userFolder->getPath(), 'Rolls');

		$exists = $this->storage->nodeExists($videoFolderName);

		if (!$exists) {
			$this->storage->newFolder($videoFolderName);
		}

		$uuid = Uuid::v4()->__toString();
		$videoFolder = $this->storage->get($videoFolderName);

		$foldernameBase = Funcs::joinPaths($videoFolder->getPath(),  'Roll_' . $uuid);
		$folderName = Funcs::ensureUniqueFileName($this->storage, $foldernameBase);

		$folder = $this->storage->newFolder($folderName);
		$tbFolder = $folder->newFolder('.thumbnails');

		$videoFilename = 'Roll-' . $uuid . '.' . $ext;
		$filename = Funcs::joinPaths($folder->getPath(), $videoFilename);

		$videoFile = $this->storage->newFile($filename, $requestFile);
		$tbFolder->newFile($videoFilename . '.' . $tbExt, $thumbFile);

		$readmeFilePath = Funcs::joinPaths($folder->getPath(), 'README.md');
		$this->storage->newFile($readmeFilePath, $requestText);

		$roll = $this->db->create($uuid, $videoFile->getId(), $folder->getId(), $user->getUID());

		return $roll;
	}

	public function saveChunk(string $rollName, $chunk, int $index)
	{
		$rollsFolderPath = $this->getRollsFolder();

		if (empty($rollName)) {
			throw new Exception('Missing roll folder');
		}

		/** @var \OCP\Files\Folder */
		$rollFolder = null;
		$rollFolderPath = Funcs::joinPaths($rollsFolderPath, $rollName);

		if (!$this->storage->nodeExists($rollFolderPath)) {
			$rollFolder = $this->storage->newFolder($rollFolderPath);
		} else {
			$rollFolder = $this->storage->get($rollFolderPath);
		}
		
		if (!$rollFolder->getStorage()->isLocal()) {
			throw new Exception('Chunked upload is only available on local mount points');
		}

		$videoFilePath = Funcs::joinPaths($rollFolderPath, 'roll.webm');

		if ($index === 0) {
			$this->storage->newFile($videoFilePath, $chunk);
		} else {
			$videoFile = $this->storage->get($videoFilePath);
			$pth = $videoFile->getInternalPath();
			$fp = $videoFile->getStorage()->fopen($pth, 'a');
			// file_put_contents($p, $chunk, FILE_APPEND)
			// if (!$fp = fopen($p, 'a')) {
			// 	throw new Exception('File cannot be opened in append mode');
			// 	}
				
			/** @var string $chunk */
			$data = stream_get_contents($chunk);
			if (fwrite($fp, $data) === false) {
				throw new Exception('Errore while writing to file');	
			}

			fclose($fp);
			
			$videoFile->touch();
		}
	}

	public function getRollsFolder(): string
	{
		$user = $this->session->getUser();
		$userFolder = $this->storage->getUserFolder($user->getUID());
		// Todo: should be a variable
		return  Funcs::joinPaths($userFolder->getPath(), 'Rolls');
	}

	public function createNewRollFolder(): string
	{
		$videoFolderName = $this->getRollsFolder();
		$exists = $this->storage->nodeExists($videoFolderName);

		if (!$exists) {
			$this->storage->newFolder($videoFolderName);
		}

		$rollsFolder = $this->storage->get($videoFolderName);

		$uuid = Uuid::v4()->__toString();
		$rollFolderName = 'Roll_' . $uuid;
		$folderName = Funcs::joinPaths($rollsFolder->getPath(), $rollFolderName);

		while ($this->storage->nodeExists($folderName)) {
			$uuid = Uuid::v4()->__toString();
			$rollFolderName = 'Roll_' . $uuid;
			$folderName = Funcs::joinPaths($rollsFolder->getPath(),  $rollFolderName);
		}

		$this->storage->newFolder($folderName);

		return $rollFolderName;
	}

	public function deleteRoll(Roll $roll)
	{
		$nodes = $this->storage->getById(
			intval($roll->getVideoFolder())
		);

		foreach ($nodes as $node) {
			$node->delete();
		}

		$this->db->delete($roll);
	}

	public function getRolls(?string $uuid): array
	{
		$user = $this->session->getUser();

		$entities = [];

		if ($uuid) {
			$item = $this->db->find($uuid);

			if (sizeof($item)) {
				$entities[] = $item[0];
			}
		} else {
			$entities = $this->db->findAll($user);
		}

		$userFolder = $this->storage->getUserFolder($user->getUID());

		$output = [];
		foreach ($entities as $entity) {
			$id = intval($entity->getVideoFile());

			$files = null;

			try {
				$files = $userFolder->getById($id);
			} catch (\OCP\Files\NotFoundException $e) {
				continue;
			}

			if (!count($files)) {
				continue;
			}

			$file = $files[0];

			if (!($file instanceof \OCP\Files\File)) {
				continue;
			}

			$thumbnailNode = null;
			$textFileNode = null;

			try {
				$readmeFilePath = Funcs::joinPaths($file->getParent()->getPath(), 'README.md');
				$textFileNode = $this->storage->get($readmeFilePath);
			} catch (\OCP\Files\NotFoundException $e) {
				// pass
			}

			$tbPath = Funcs::joinPaths($file->getParent()->getPath(), '.thumbnails');
			if ($this->storage->nodeExists($tbPath)) {
				$tbFolder = $this->storage->get($tbPath);
				$thumbnails = $tbFolder->getDirectoryListing();

				foreach ($thumbnails as $t) {
					// Check if a thumbnail for the given file exists,
					// The thumbnail MUST have the same filename except for the file extension
					$tm = pathinfo($t->getPath(), PATHINFO_FILENAME);
					$fm = basename($file->getPath());

					if ($tm === $fm) {
						$thumbnailNode = $t;
						break;
					}
				}
			}

			$output[] = new RollListItem($entity, $file, $thumbnailNode, $textFileNode);
		}

		return $output;
	}
}
