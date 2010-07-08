<?php

namespace DoctrineExtensions\Hierarchical\NestedSet;

use DoctrineExtensions\Hierarchical\AbstractDecorator,
    DoctrineExtensions\Hierarchical\NodeInterface,
    DoctrineExtensions\Hierarchical\HierarchicalException,
    Doctrine\ORM\NoResultException;

/**
 * TODO comment/finish this class
 *
 * TODO if you want to return Nodes in getChildren and others,
 *      we need a custom iterator class that can fetch items from
 *      the result set and turn them into nodes on the fly, we can
 *      not just fetch everything at once imo memory wise it's bad,
 *      and imo it should be optional anyway, or just never do it,
 *      because when you fetch nodes to display them, you don't care
 *      about having this decorator around each of them. Also that is
 *      why I added the HierarchicalManager::getNodes() function, this
 *      should be up to the user imo.
 *
 * TODO all inserts/move methods should read the left/right values
 *      from the parent from the DB and not the given node, otherwise
 *      we might have race conditions that break the tree
 *
 * TODO getIdFieldName and others should be cached imo, and lazy-loaded
 *      by _getSelectQueryBuilder if they are not filled in
 */
class NestedSetNodeDecorator extends AbstractDecorator implements NodeInterface
{
    protected $parent;
    protected $children;

    public function getId()
    {
        return $this->getValue($this->getIdFieldName());
    }

    public function getDepth()
    {
        return $this->getValue($this->getLevelFieldName());
    }

    public function getRoot()
    {
        return $this->getValue($this->getRootIdFieldName());
    }

    public function isRoot()
    {
        return ! $this->hasParent();
    }

    public function isLeaf()
    {
        return ! $this->hasChildren();
    }

    public function isValid()
    {
        $rightValue = $this->getValue($this->getRightFieldName());
        $leftValue  = $this->getValue($this->getLeftFieldName());

        return ($rightValue > $leftValue);
    }
    
    public function getSiblings()
    {
        return $this->getParent()->getChildren();
    }

    public function getFirstSibling()
    {
        return $this->getParent()->getChildren(1, 0, 'ASC');
    }

    public function getLastSibling()
    {
        return $this->getParent()->getChildren(1, 0, 'DESC');
    }

    public function getPrevSibling()
    {
        $parent = $this->getParent();
        
        $field = $this->getLeftFieldName();
        $offset = ($this->getValue($fields) - $parent->getValue($field)) / 2;
        $offset -= 1;
        
        return $parent->getChildren(1, $offset, 'ASC');
    }

    public function getNextSibling()
    {
        $parent = $this->getParent();
        
        $field = $this->getLeftFieldName();
        $offset = ($this->getValue($fields) - $parent->getValue($field)) / 2;
        $offset += 1;
        
        return $parent->getChildren(1, $offset, 'ASC');
    }

    public function isSiblingOf($entity)
    {
        $parent = $this->getParent();
        // TODO: implement
        return true;
    }

    public function hasChildren()
    {
        $rightValue = $this->getValue($this->getRightFieldName());
        $leftValue  = $this->getValue($this->getLeftFieldName());

        return ($rightValue - $leftValue) > 1;
    }

    public function getChildren($limit = null, $offset = 0, $order = 'ASC')
    {
        if ($this->children) {
            return $this->children;
        }

        return $this->children = $this->getDescendants(1, $limit, $offset, $order);
    }

    public function getFirstChild()
    {
        if ($this->children) {
            if (count($this->children) > 0) {
                return $this->children[0];
            }

            throw new NoResultException;
        }

        $qb = $this->hm->getQueryFactory()->getSelectQueryBuilder();
        $expr = $qb->expr();
        $andX = $expr->andX();
        $rootId = $this->getValue($this->getRootIdFieldName()) ?: $this->getValue($this->getIdFieldName());
        $andX->add($expr->eq('e.' . $this->getRootIdFieldName(), $rootId));
        $andX->add($expr->eq('e.' . $this->getLeftFieldName(), $this->getValue($this->getLeftFieldName()) + 1));
        $qb->where($andX);

        return $this->hm->getNode($qb->getQuery()->getSingleResult());
    }

    public function getLastChild()
    {
        if ($this->children) {
            if (count($this->children) > 0) {
                return end($this->children);
            }

            throw new NoResultException;
        }

        $qb = $this->hm->getQueryFactory()->getSelectQueryBuilder();
        $expr = $qb->expr();
        $andX = $expr->andX();
        $rootId = $this->getValue($this->getRootIdFieldName()) ?: $this->getValue($this->getIdFieldName());
        $andX->add($expr->eq('e.' . $this->getRootIdFieldName(), $rootId));
        $andX->add($expr->eq('e.' . $this->getRightFieldName(), $this->getValue($this->getRightFieldName()) - 1));
        $qb->where($andX);

        return $this->hm->getNode($qb->getQuery()->getSingleResult());
    }

    public function getNumberOfChildren()
    {
        return count($this->getChildren());
    }

    public function isChildOf($entity)
    {
        // TODO: implement
    }
    
    public function hasDescendants()
    {
        return $this->hasChildren();
    }
    
    public function getDescendants($depth = 0, $limit = null, $offset = 0, $order = 'ASC')
    {
        if ( ! $this->hasChildren()) {
            return array();
        }
        $qb = $this->hm->getQueryFactory()->getSelectQueryBuilder();
        $expr = $qb->expr();
        $andX = $expr->andX();
        $rootId = $this->getValue($this->getRootIdFieldName()) ?: $this->getValue($this->getIdFieldName());
        $andX->add($expr->eq('e.' . $this->getRootIdFieldName(), $rootId));
        $andX->add($expr->gt('e.' . $this->getLeftFieldName(), $this->getValue($this->getLeftFieldName())));
        $andX->add($expr->lt('e.' . $this->getRightFieldName(), $this->getValue($this->getRightFieldName())));

        if ($depth > 0) {
            $andX->add($expr->lte(
                'e.' . $this->getLevelFieldName(), $this->getValue($this->getLevelFieldName()) + $depth
            ));
        }

        $qb->where($andX)->orderBy('e.' . $this->getLeftFieldName(), $order);

        $q = $qb->getQuery();
        if ($limit !== null) {
            $q->setMaxResults((int) $limit);
        }
        if ($offset) {
            $q->setFirstResult((int) $offset);
        }

        return $q->getResult();
    }

    public function getNumberOfDescendants()
    {
        $rightValue = $this->getValue($this->getRightFieldName());
        $leftValue  = $this->getValue($this->getLeftFieldName());

        return ($rightValue - $leftValue - 1) / 2;
    }

    public function isDescendantOf($entity)
    {
        // TODO: implement
    }
    
    public function hasParent()
    {
        return $this->getValue($this->getLevelFieldName()) != 0;
    }

    public function getParent()
    {
        if ( ! $this->parent) {
            if ( ! $this->hasParent()) {
                throw new \Exception('Cannot get parent node for root entity');
            }
            
            $queryBuilder = $this->hm->getQueryFactory()->getSelectQueryBuilder();
            $queryBuilder->where($queryBuilder->expr()->eq(
                'e.' . $this->prototype->getLevelFieldName(),
                $this->getValue($this->prototype->getLevelFieldName()) - 1
            ));
            
            $queryBuilder->andWhere($queryBuilder->expr()->lt(
                'e.' . $this->prototype->getLeftFieldName(),
                $this->getValue($this->prototype->getLeftFieldName())
            ));
            
            $queryBuilder->andWhere($queryBuilder->expr()->gt(
                'e.' . $this->prototype->getRightFieldName(),
                $this->getValue($this->prototype->getRightFieldName())
            ));
            
            if ($this->prototype->hasManyRoots()) {
                $queryBuilder->andWhere($queryBuilder->expr()->eq(
                    'e.' . $this->prototype->getRootIdFieldName(),
                    $this->getValue($this->prototype->getRootIdFieldName())
                ));
            }
            
            $this->parent = $this->hm->getNode($queryBuilder->getQuery()->setMaxResults(1)->getSingleResult());
        }
        
        return $this->parent;
    }

    public function hasAncestors()
    {
        return $this->hasParent();
    }

    public function getAncestors($depth = null, $limit = null, $offset = 0, $order = 'ASC')
    {
        if ( ! $this->hasChildren()) {
            return array();
        }

        $qb = $this->hm->getQueryFactory()->getSelectQueryBuilder();
        $expr = $qb->expr();
        $andX = $expr->andX();
        $andX->add($expr->eq('e.' . $this->getRootIdFieldName(), $this->getValue($this->getRootIdFieldName())));
        $andX->add($expr->lt('e.' . $this->getLeftFieldName(), $this->getValue($this->getLeftFieldName())));
        $andX->add($expr->gt('e.' . $this->getRightFieldName(), $this->getValue($this->getRightFieldName())));

        if ($depth > 0) {
            $andX->add($expr->lte(
                'e.' . $this->getLevelFieldName(), $this->getValue($this->getLevelFieldName()) - $depth
            ));
        }

        $qb->where($andX)->orderBy('e.' . $this->getLeftFieldName(), $order);

        $q = $qb->getQuery();
        if ($limit !== null) {
            $q->setMaxResults((int) $limit);
        }
        if ($offset) {
            $q->setFirstResult((int) $offset);
        }

        return $q->getResult();
    }

    public function insertAsChildOf($entity, $pos = null)
    {
        
    }

    public function insertAsPrevSiblingOf($entity)
    {
        $node = $this->_getNode($entity);
        $entity = $node->unwrap();
        if ($entity === $this->entity) {
            throw new \IllegalArgumentException('Node cannot be added as sibling of itself.');
        }

        $newLeft = $this->classMetadata->reflFields[$this->getLeftFieldName()]->getValue($entity);
        $newRight = $newLeft + 1;
        $newLevel = $this->classMetadata->reflFields[$this->getLevelFieldName()]->getValue($entity);
        $newRoot = $this->classMetadata->reflFields[$this->getRootIdFieldName()]->getValue($entity);
        if (!$newRoot) {
            throw new \IllegalArgumentException('You can not add a sibling to a root node');
        }

        $this->_shiftRLValues($newLeft, 0, 2, $newRoot);

        $this->setValue($this->getLevelFieldName(), $newLevel);
        $this->setValue($this->getLeftFieldName(), $newLeft);
        $this->setValue($this->getRightFieldName(), $newRight);
        $this->setValue($this->getRootIdFieldName(), $newRoot);

        $this->hm->getEntityManager()->persist($this->entity);
    }

    public function insertAsNextSiblingOf($entity)
    {
        $node = $this->_getNode($entity);
        $entity = $node->unwrap();
        if ($entity === $this->entity) {
            throw new \IllegalArgumentException('Node cannot be added as sibling of itself.');
        }

        $newLeft = $this->classMetadata->reflFields[$this->getRightFieldName()]->getValue($entity) + 1;
        $newRight = $newLeft + 1;
        $newLevel = $this->classMetadata->reflFields[$this->getLevelFieldName()]->getValue($entity);
        $newRoot = $this->classMetadata->reflFields[$this->getRootIdFieldName()]->getValue($entity);
        if (!$newRoot) {
            throw new \IllegalArgumentException('You can not add a sibling to a root node');
        }

        $this->_shiftRLValues($newLeft, 0, 2, $newRoot);

        $this->setValue($this->getLevelFieldName(), $newLevel);
        $this->setValue($this->getLeftFieldName(), $newLeft);
        $this->setValue($this->getRightFieldName(), $newRight);
        $this->setValue($this->getRootIdFieldName(), $newRoot);

        $this->hm->getEntityManager()->persist($this->entity);
    }

    public function insertAsLastChildOf($entity)
    {
        $node = $this->_getNode($entity);
        $entity = $node->unwrap();
        if ($entity === $this->entity) {
            throw new \IllegalArgumentException('Node cannot be added as child of itself.');
        }

        $newLeft = $this->classMetadata->reflFields[$this->getRightFieldName()]->getValue($entity);
        $newRight = $newLeft + 1;
        $newLevel = $this->classMetadata->reflFields[$this->getLevelFieldName()]->getValue($entity) + 1;
        $newRoot = $this->classMetadata->reflFields[$this->getRootIdFieldName()]->getValue($entity);
        if (!$newRoot) {
            $newRoot = $this->classMetadata->reflFields[$this->getIdFieldName()]->getValue($entity);
        }

        $this->_shiftRLValues($newLeft, 0, 2, $newRoot);

        $this->setValue($this->getLevelFieldName(), $newLevel);
        $this->setValue($this->getLeftFieldName(), $newLeft);
        $this->setValue($this->getRightFieldName(), $newRight);
        $this->setValue($this->getRootIdFieldName(), $newRoot);
        
        $this->hm->getEntityManager()->persist($this->entity);
    }

    public function insertAsFirstChildOf($entity)
    {
        $node = $this->_getNode($entity);
        $entity = $node->unwrap();
        if ($entity === $this->entity) {
            throw new \IllegalArgumentException('Node cannot be added as child of itself.');
        }

        $newLeft = $this->classMetadata->reflFields[$this->getLeftFieldName()]->getValue($entity) + 1;
        $newRight = $newLeft + 1;
        $newLevel = $this->classMetadata->reflFields[$this->getLevelFieldName()]->getValue($entity) + 1;
        $newRoot = $this->classMetadata->reflFields[$this->getRootIdFieldName()]->getValue($entity);
        if (!$newRoot) {
            $newRoot = $this->classMetadata->reflFields[$this->getIdFieldName()]->getValue($entity);
        }

        $this->_shiftRLValues($newLeft, 0, 2, $newRoot);

        $this->setValue($this->getLevelFieldName(), $newLevel);
        $this->setValue($this->getLeftFieldName(), $newLeft);
        $this->setValue($this->getRightFieldName(), $newRight);
        $this->setValue($this->getRootIdFieldName(), $newRoot);

        $this->hm->getEntityManager()->persist($this->entity);
    }
    
    public function moveAsChildOf($target, $pos = null)
    {
        
    }
    
    public function moveAsLastChildOf($entity)
    {
        $node = $this->_getNode($entity);
        $entity = $node->unwrap();
        if ($entity === $this->entity) {
            throw new \IllegalArgumentException('Node cannot be added as child of itself.');
        }

        // TODO implement
    }

    public function moveAsFirstChildOf($entity)
    {
        $node = $this->_getNode($entity);
        $entity = $node->unwrap();
        if ($entity === $this->entity) {
            throw new \IllegalArgumentException('Node cannot be added as child of itself.');
        }

        // TODO implement
    }

    public function moveAsNextSiblingOf($entity)
    {
        $node = $this->_getNode($entity);
        $entity = $node->unwrap();
        if ($entity === $this->entity) {
            throw new \IllegalArgumentException('Node cannot be added as child of itself.');
        }

        // TODO implement
    }

    public function moveAsPrevSiblingOf($entity)
    {
        $node = $this->_getNode($entity);
        $entity = $node->unwrap();
        if ($entity === $this->entity) {
            throw new \IllegalArgumentException('Node cannot be added as child of itself.');
        }

        // TODO implement
    }
    
    public function delete()
    {
        $oldRoot = $this->getValue($this->getRootIdFieldName());

        $qb = $this->hm->getQueryFactory()->getBaseQueryBuilder();
        $expr = $qb->expr();
        $andX = $expr->andX();
        $andX->add($expr->eq('e.' . $this->getRootIdFieldName(), $this->getValue($this->getRootIdFieldName())));
        $andX->add($expr->gte('e.' . $this->getLeftFieldName(), $this->getValue($this->getLeftFieldName())));
        $andX->add($expr->lte('e.' . $this->getRightFieldName(), $this->getValue($this->getRightFieldName())));
        $qb->delete()->where($andX);

        $qb->getQuery()->execute();

        $first = $this->getValue($this->getLeftFieldName());
        $delta = $leftValue - $this->getValue($this->getRightFieldName()) - 1;
        $this->_shiftRLValues($first, 0, $delta, $oldRoot);
    }

    protected function _shiftRLValues($first, $last, $delta, $root)
    {
        $this->_updateLeftValues($first, $last, $delta, $root);
        $this->_updateRightValues($first, $last, $delta, $root);
    }

    protected function _updateLeftValues($minLeft, $maxLeft, $delta, $rootId)
    {
        $qb = $this->hm->getQueryFactory()->getBaseQueryBuilder();
        $expr = $qb->expr();
        $andX = $expr->andX();

        $orX = $expr->orX();
        $orX->add($expr->eq('e.' . $this->getIdFieldName(), $rootId));
        $orX->add($expr->eq('e.' . $this->getRootIdFieldName(), $rootId));
        $andX->add($orX);

        $andX->add($expr->gte('e.' . $this->getLeftFieldName(), $minLeft));
        if ($maxLeft != 0) {
            $andX->add($expr->lte('e.' . $this->getLeftFieldName(), $maxLeft));
        }
        $qb->where($andX);
        $qb->update()->set('e.' . $this->getLeftFieldName(), 'e.' . $this->getLeftFieldName() . ' + ' . $delta);
        $qb->getQuery()->execute();
    }

    protected function _updateRightValues($minRight, $maxRight, $delta, $rootId)
    {
        $qb = $this->hm->getQueryFactory()->getBaseQueryBuilder();
        $expr = $qb->expr();
        $andX = $expr->andX();

        $orX = $expr->orX();
        $orX->add($expr->eq('e.' . $this->getIdFieldName(), $rootId));
        $orX->add($expr->eq('e.' . $this->getRootIdFieldName(), $rootId));
        $andX->add($orX);

        $andX->add($expr->gte('e.' . $this->getRightFieldName(), $minRight));
        if ($maxRight != 0) {
            $andX->add($expr->lte('e.' . $this->getRightFieldName(), $maxRight));
        }
        $qb->where($andX);
        $qb->update()->set('e.' . $this->getRightFieldName(), 'e.' . $this->getRightFieldName() . ' + ' . $delta);
        $qb->getQuery()->execute();
    }

    protected function _updateLevelValues($left, $right, $delta, $rootId)
    {
        $qb = $this->hm->getQueryFactory()->getBaseQueryBuilder();
        $expr = $qb->expr();
        $andX = $expr->andX();

        $orX = $expr->orX();
        $orX->add($expr->eq('e.' . $this->getIdFieldName(), $rootId));
        $orX->add($expr->eq('e.' . $this->getRootIdFieldName(), $rootId));
        $andX->add($orX);

        $andX->add($expr->gt('e.' . $this->getLeftFieldName(), $left));
        $andX->add($expr->lt('e.' . $this->getRightFieldName(), $right));
        $qb->where($andX);
        $qb->update()->set('e.' . $this->getLevelFieldName(), 'e.' . $this->getLevelFieldName() . ' + ' . $delta);
        $qb->getQuery()->execute();
    }

    // Delegate support for Decorator object

    public function getIdFieldName()
    {
        return $this->entity->getIdFieldName();
    }

    public function getLeftFieldName()
    {
        return $this->entity->getLeftFieldName();
    }

    public function getRightFieldName()
    {
        return $this->entity->getRightFieldName();
    }

    public function getLevelFieldName()
    {
        return $this->entity->getLevelFieldName();
    }

    public function getRootIdFieldName()
    {
        return $this->entity->getRootIdFieldName();
    }
}