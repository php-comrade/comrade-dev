<?php
namespace App\Storage;

use App\Model\JobTemplate;
use Makasim\Yadm\Storage;

/**
 * @method JobTemplate|null create()
 * @method JobTemplate|null findOne(array $filter = [], array $options = [])
 * @method JobTemplate[]|\Traversable find(array $filter = [], array $options = [])
 */
class JobTemplateStorage extends Storage
{
    public function findParentSubJobByNameSubJobs(string $parentJobId, string $subJobName): ?JobTemplate
    {
        return $this->findOne([
            'subJobPolicy.parentId' => $parentJobId,
            'name' => $subJobName
        ]);
    }
}
