<?php

namespace OCA\Rolls\Db;

use JsonSerializable;
use OCA\Rolls\Utils\PhpTypes;
use OCP\AppFramework\Db\Entity;
use OCP\IUser;
use ReturnTypeWillChange;

class Roll extends Entity {
    protected $uuid;
    protected $videoFile;
    protected $videoFolder;
    protected $owner;
    protected $createdAt;

    public function __construct() {
        $this->addType('uuid', PhpTypes::STRING);
        $this->addType('video_file', PhpTypes::INTEGER);
        $this->addType('video_folder', PhpTypes::INTEGER);
        $this->addType('owner', PhpTypes::STRING);
        $this->addType('created_at', PhpTypes::INTEGER);
    }
}
