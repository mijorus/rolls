<?php

namespace OCA\Rolls\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\IUserSession;
use PDO;
use Symfony\Component\Uid\Uuid;

class RollsDb extends QBMapper {

	protected $db;

	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'rolls_videos', 'OCA\Rolls\Db\Roll');
	}

	public function find(string $uuid) {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->tableName)
			->where(
				$qb->expr()->eq('uuid', $qb->createNamedParameter($uuid, IQueryBuilder::PARAM_STR))
			);

		return $this->findEntity($qb);
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
		$sqb->select('t.*')
			->where(
				$sqb->expr()->neq('t.owner', $sqb->createNamedParameter($user->getUID(), IQueryBuilder::PARAM_STR))
			)
			->where(
				$sqb->expr()->eq('s.share_with', $sqb->createNamedParameter($user->getUID(), IQueryBuilder::PARAM_STR))
			)
			->from($this->tableName, 't')
			->leftJoin('t', 'share', 's', 't.video_file = s.file_source')
			->orderBy('t.id', 'desc');

		$entities = array_merge($entities, $this->findEntities($sqb));

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
