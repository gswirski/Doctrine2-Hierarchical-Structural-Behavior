<?php

namespace DoctrineExtensions\Hierarchical;

use Doctrine\ORM\Mapping\ClassMetadata;

class AbstractQueryFactory
{
    /**
     * @var DoctrineExtensions\Hierarchical\MaterializedPath\MaterializedPathManager
     */
    protected $hm;

    /**
     * @var Doctrine\ORM\Mapping\ClassMetadata
     */
    protected $classMetadata;

    /**
     * ReadOnly prototype entity grabbed from ClassMetadata
     *
     * @var string
     **/
    protected $prototype;

    public function __construct(AbstractManager $hm, ClassMetadata $meta)
    {
        $this->hm = $hm;
        $this->classMetadata = $meta;
        $this->prototype = $meta->newInstance();
    }
    
    /**
     * Returns a basic QueryBuilder which will select the entire table ordered by path
     *
     * @param  $node
     * @return void
     */
    public function getBaseQueryBuilder()
    {
        return $this->hm->getEntityManager()
            ->createQueryBuilder()
            ->select('e')
            ->from($this->classMetadata->name, 'e');
    }
}
