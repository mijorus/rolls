<?php

namespace OCA\Rolls\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\IRootFolder;
use OCP\Files\Storage\IStorage;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Share\IManager;
use PDO;
use Symfony\Component\Uid\Uuid;

class RollsDb extends QBMapper {

	protected $db;
	private $shareManager;
	private $storage;

	public function __construct(IDBConnection $db, IManager $shareManager, IRootFolder $storage) {
		parent::__construct($db, 'rolls_videos', 'OCA\Rolls\Db\Roll');
		$this->shareManager = $shareManager;
		$this->storage = $storage;
	}

	public function find(string $uuid) {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->tableName)
			->where(
				$qb->expr()->eq('uuid', $qb->createNamedParameter($uuid, IQueryBuilder::PARAM_STR))
			);

		return $this->findEntities($qb);
	}

	public function findAll(IUser $user) {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->tableName)
			->where(
				$qb->expr()->eq('owner', $qb->createNamedParameter($user->getUID(), IQueryBuilder::PARAM_STR))
			)
			->orderBy('id', 'desc');

		$entities = $this->findEntities($qb);

		$sqb = $this->db->getQueryBuilder();
		$sqb->select('*')
			->from($this->tableName)
			->where(
				$sqb->expr()->neq('owner', $sqb->createNamedParameter($user->getUID(), IQueryBuilder::PARAM_STR))
			)
			->orderBy('id', 'desc');

		$externalShares = [];
		foreach ($this->findEntities($sqb) as $s) {
			$nodes = $this->storage->getById($s->getVideoFolder());

			foreach ($nodes as $node) {
				if ($node) {
					$shareList = $this->shareManager->getAccessList($node, false, false);
					$accessUsers = $shareList['users'] ?? [];
					if (in_array($user->getUID(), $accessUsers)) {
						$externalShares[] = $s;
						break;
					}
				}
			}
		}

		$entities = array_merge($entities, $externalShares);

		return $entities;
	}

	public function create(string $uuid, int $fileId, int $folderId, string $owner): Roll {
		$entity = new Roll();
		$entity->setUuid($uuid);
		$entity->setVideoFile($fileId);
		$entity->setVideoFolder($folderId);
		$entity->setOwner($owner);
		$entity->setCreatedAt(time());

		$entity = $this->insert($entity);
		return $entity;
	}
}
