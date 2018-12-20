<?php
namespace App\Storage;

use App\Model\PvmToken;
use Makasim\Yadm\Storage;

/**
 * @method PvmToken|null create()
 * @method PvmToken|null findOne(array $filter = [], array $options = [])
 * @method PvmToken[]|\Traversable find(array $filter = [], array $options = [])
 */
class TokenStorage extends Storage
{
}
