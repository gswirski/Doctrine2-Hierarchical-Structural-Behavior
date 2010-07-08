<?php

namespace DoctrineExtensions\Hierarchical\NestedSet;

use DoctrineExtensions\Hierarchical\AbstractQueryFactory;

class NestedSetQueryFactory extends AbstractQueryFactory
{   
    public function getSelectQueryBuilder()
    {
        return $this->getBaseQueryBuilder()
            ->orderBy('e.' . $this->prototype->getLeftFieldName());
    }
    
    /**
     * Returns a QueryBuilder for all root nodes in tree
     *
     * @param Node $node
     * @return QueryBuilder
     **/
    public function getRootNodeQueryBuilder()
    {
        $qb = $this->getBaseQueryBuilder();
        $qb->where($qb->expr()->eq('e.' . $this->prototype->getLevelFieldName(), 0));
        $qb->orderBy('e.' . $this->prototype->getRootIdFieldName());
        return $qb;
    }
}
