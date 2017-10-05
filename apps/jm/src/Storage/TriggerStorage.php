<?php
namespace App\Storage;

use Comrade\Shared\Model\Trigger;
use Makasim\Yadm\Storage;

/**
 * @method Trigger|null create()
 * @method Trigger|null findOne(array $filter = [], array $options = [])
 * @method Trigger[]|\Traversable find(array $filter = [], array $options = [])
 */
class TriggerStorage extends Storage
{
    /**
     * @param string $id
     * @return Trigger
     */
    public function getOneById(string $id):Trigger
    {
        if (false == $trigger = $this->findOne(['id' => $id])) {
            throw new \LogicException(sprintf('The trigger with id "%s" could not be found', $id));
        }

        return $trigger;
    }
}
