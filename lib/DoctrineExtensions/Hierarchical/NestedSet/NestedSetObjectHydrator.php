<?php

namespace DoctrineExtensions\Hierarchical\NestedSet;

use Doctrine\ORM\Internal\Hydration\ObjectHydrator;

/**
 * @todo Support multiple roots
 * @todo Support hierarchies in OneToMany relations
 */
class NestedSetObjectHydrator extends ObjectHydrator
{
    protected function _hydrateAll()
    {
        $className = reset($this->_rsm->aliasMap);
        $class = new \ReflectionClass($className);
        
        if ( ! $class->isSubclassOf('DoctrineExtensions\Hierarchical\NestedSet\NestedSetNodeInfo')) {
            throw new \Exception('Only instances of NestedSetNodeInfo can be hydrated by NestedSetObjectHydrator');
        }
        
        $prototype = $this->_em->getClassMetadata($className)->newInstance();
        $levelFieldName = $prototype->getLevelFieldName();
        
        $collection = parent::_hydrateAll();
        
        // Trees mapped
        $trees = array();
        $l = 0;

        if (count($collection) > 0) {
            // Node Stack. Used to help building the hierarchy
            $stack = array();

            foreach ($collection as $child) {
                $item = $child;

                //$item['__children'] = array();

                // Number of stack items
                $l = count($stack);

                // Check if we're dealing with different levels
                while($l > 0 && $this->getValue($levelFieldName, $className, $stack[$l - 1]) >= $this->getValue($levelFieldName, $className, $item)) {
                    array_pop($stack);
                    $l--;
                }

                // Stack is empty (we are inspecting the root)
                if ($l == 0) {
                    // Assigning the root child
                    $i = count($trees);
                    $trees[$i] = $item;
                    $stack[] = & $trees[$i];
                } else {
                    // Add child to parent
                    $i = count($stack[$l - 1]->getChildren());
                    $stack[$l - 1]->addChild($item, $i);
                    $stack[] = $stack[$l - 1]->getChild($i);
                }
            }
        }
        return $trees;
    }
    
    protected function getValue($field, $class, $entity)
    {
        return $this->_em->getClassMetadata($class)->reflFields[$field]->getValue($entity);
    }
    
    public function setValue($field, $value, $class, $entity)
    {
        $this->_em->getClassMetadata($class)->reflFields[$field]->setValue($entity, $value);
    }
}