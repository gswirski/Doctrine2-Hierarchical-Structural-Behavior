<?php

namespace DoctrineExtensions\Hierarchical\NestedSet;

class NestedSetQueryFactory extends AbstractQueryFactory
{
    /**
     * Returns a basic QueryBuilder which will select the entire table ordered by path
     *
     * @param  $node
     * @return void
     */
    public function getBaseQueryBuilder()
    {
        return parent::getBaseQueryBuilder()
            ->orderBy('e.' . $this->prototype->getLeftFieldName());
    }
}
