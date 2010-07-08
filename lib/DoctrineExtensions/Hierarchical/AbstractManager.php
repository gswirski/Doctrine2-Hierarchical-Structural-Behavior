<?php

namespace DoctrineExtensions\Hierarchical;

use Doctrine\ORM\EntityManager,
    Doctrine\ORM\Mapping\ClassMetadata,
    Doctrine\ORM\PersistentCollection;


abstract class AbstractManager
{
    /**
     * @var Doctrine\ORM\EntityManager
     */
    protected $em;

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

    protected $queryFactory;
    
    /**
     * __construct
     *
     * @param Doctrine\ORM\EntityManager $em
     * @param Doctrine\ORM\Mapping\ClassMetadata $meta
     * @return void
     */
    public function __construct(EntityManager $em, ClassMetadata $meta)
    {
        $this->em = $em;
        $this->classMetadata = $meta;
        $this->prototype = $meta->newInstance();
    }

    /**
     * EntityManager accessor
     *
     * @return Doctrine\ORM\EntityManager
     */
    public function getEntityManager()
    {
        return $this->em;
    }

    /**
     * ClassMetadata accessor
     *
     * @return Doctrine\ORM\Mapping\ClassMetadata
     */
    public function getClassMeta()
    {
        return $this->classMetadata;
    }

    /**
     * Decorates a collection of entities as Nodes
     *
     * @todo I think this shoud build correct tree, not only wrap everything into decorator
     * @param Traversable|array $input
     * @return Traversable|array
     */
    public function getNodes($input)
    {
        if ($input instanceof PersistentCollection) {
            // Return instance of ArrayCollection instead of PersistentCollection
            $hm = $this;
            return $input->unwrap()->map(
                function ($node) use ($hm) {
                    return $hm->getNode($node);
                }
            );
        } elseif (is_array($input) || $input instanceof Traversable) {
            foreach ($input as $key => $entity) {
                $input[$key] = $this->getNode($entity);
            }
            return $input;
        }

        throw new \InvalidArgumentException(
            'Input to getNodes should be a PersistentCollection or a ' .
            'Traversable/array, ' . gettype($input) . ' provided.'
        );
    }

    /**
     * Returns the QueryBuilder factory
     *
     * @return AbstractQueryFactory
     **/
    public function getQueryFactory()
    {
        return $this->queryFactory;
    }

    /**
     * Decorates the entity with the appropriate Node decorator
     *
     * @param mixed $entity
     * @return DoctrineExtensions\Hierarchical\Node
     */
    abstract public function getNode($entity);

    /**
     * Adds the entity as a root node
     *
     * Decorates via getNode() as needed
     *
     * @param mixed $entity
     * @return DoctrineExtensions\Hierarchical\Node
     */
    abstract public function createRoot($entity);
    
    /**
     * Gets root node by id or default one if not specified
     *
     * @param mixed $id
     * @return DoctrineExtensions\Hierarchical\Node
     */
    abstract public function getRoot($id = null);
    
    /**
     * Gets all root nodes
     *
     * @return Doctrine\Common\Collections\Collection
     */
    abstract public function getRoots();
    
    /**
     * Deletes tree by root id or default one if not specified
     *
     * @param mixed $root
     */
    abstract public function deleteTree($root = null);
    
    /**
     * Deletes all trees
     */
    abstract public function deleteTrees();
}
