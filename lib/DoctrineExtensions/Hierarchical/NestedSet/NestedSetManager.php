<?php

namespace DoctrineExtensions\Hierarchical\NestedSet;

use DoctrineExtensions\Hierarchical\AbstractManager,
    DoctrineExtensions\Hierarchical\NodeInterface,
    DoctrineExtensions\Hierarchical\NestedSet\NestedSetNodeInfo,
    DoctrineExtensions\Hierarchical\NestedSet\NestedSetNodeDecorator;

class NestedSetManager extends AbstractManager implements NestedSetNodeInfo
{
    /**
     * Decorates the entity with the appropriate Node decorator
     *
     * @param mixed $entity
     * @return DoctrineExtensions\Hierarchical\Node
     */
    public function getNode($entity)
    {
        if ($entity instanceof NodeInterface) {
            if ($entity instanceof NestedSetNodeDecorator) {
                return $entity;
            } else {
                throw new \InvalidArgumentException('Provided node is not of type NestedSetNodeInfo');
            }
        } elseif (! $entity instanceof NestedSetNodeInfo) {
                throw new \InvalidArgumentException('Provided entity is not of type NestedSetNodeInfo');
        }

        return new NestedSetNodeDecorator($entity, $this);
    }

    /**
     * Adds the entity as a root node
     *
     * Decorates via getNode() as needed
     *
     * @param mixed $entity
     * @return DoctrineExtensions\Hierarchical\Node
     */
    public function addRoot($entity)
    {
        $node = $this->getNode($entity);
        $entity = $node->unwrap();
        if ($node->getId()) {
            throw new HierarchicalException('This entity is already initialized and can not be made a root node');
        }

        $this->em->getConnection()->beginTransaction();
        try {
            $node->setValue($this->getLeftFieldName(), 1);
            $node->setValue($this->getRightFieldName(), 2);
            $node->setValue($this->getLevelFieldName(), 0);
            $node->setValue($this->getRootIdFieldName(), 1);
            
            $this->em->persist($entity);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->em->close();
            throw $e;
        }
        return $node;
    }
    
    /**
     * BEGIN NestedSetNodeInfo Implementation
     **/
    
    /**
     * Retrieves the Entity identifier field name
     *
     * @return string
     */
    public function getIdFieldName()
    {
        return $this->prototype->getIdFieldName();
    }

    /**
     * Retrieves the Entity left field name
     *
     * @return string
     */
    public function getLeftFieldName()
    {
        return $this->prototype->getLeftFieldName();
    }

    /**
     * Retrieves the Entity right field name
     *
     * @return string
     */
    public function getRightFieldName()
    {
        return $this->prototype->getRightFieldName();
    }

    /**
     * Retrieves the Entity level field name
     *
     * @return string
     */
    public function getLevelFieldName()
    {
        return $this->prototype->getLevelFieldName();
    }

    /**
     * Retrieves the Entity root_id field name
     *
     * @return string
     */
    public function getRootIdFieldName()
    {
        return $this->prototype->getRootIdFieldName();
    }

    /**
     * Retrieves the Entity parent_id field name
     *
     * @return string
     */
    public function getParentIdFieldName()
    {
        return $this->prototype->getParentIdFieldName();
    }
}