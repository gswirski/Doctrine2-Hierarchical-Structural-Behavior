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
     * Creates root node from given entity
     *
     * @param object $entity        instance of Doctrine_Record
     */
    public function createRoot(NestedSetNodeInfo $entity)
    {
        $node = $this->getNode($entity);
        
        $this->em->getConnection()->beginTransaction();
        try {
            $node->setValue($this->getLeftFieldName(), 1);
            $node->setValue($this->getRightFieldName(), 2);
            $node->setValue($this->getLevelFieldName(), 0);
            
            if ( ! $node->getValue($this->getRootIdFieldName())) {
                if ( ! $node->getId()) {
                    throw new HierarchicalException('You cannot use default root id for non persisted entity');
                }
                
                $node->setValue($this->getRootIdFieldName(), $node->getId());
            }
            
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
     * Gets root node by id or default one if not specified
     *
     * @param mixed $id
     * @return DoctrineExtensions\Hierarchical\Node
     */
    public function getRoot($id = null)
    {
        
    }
    
    /**
     * Gets all root nodes
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getRoots()
    {
        
    }
    
    /**
     * Deletes tree by root id or default one if not specified
     *
     * @param mixed $root
     */
    public function deleteTree($root = null)
    {
        
    }
    
    /**
     * Deletes all trees
     */
    public function deleteTrees()
    {
        
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