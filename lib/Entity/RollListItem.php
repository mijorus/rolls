<?php

namespace OCA\Rolls\Entity;

use OCA\Rolls\Db\Roll;
use OCP\Files\File;
use OCP\Files\Node;

class RollListItem {
    public ?Node $thumbnail;
    public ?File $textFile;
    public File $file;
    public Roll $roll;

    public function __construct(Roll $roll, File $file, ?Node $thumbnail, ?File $textfile) {
        $this->roll = $roll;
        $this->file = $file;
        $this->thumbnail = $thumbnail;
        $this->textFile = $textfile;
    }
}