<?php

namespace DoctrineExtensions\Hierarchical\NestedSet;

interface NestedSetNodeInfo
{
    /**
     * Retrieves the Entity identifier field name
     *
     * @return string
     */
    public function getIdFieldName();

    /**
     * Retrieves the Entity left field name
     *
     * @return string
     */
    public function getLeftFieldName();

    /**
     * Retrieves the Entity right field name
     *
     * @return string
     */
    public function getRightFieldName();

    /**
     * Retrieves the Entity level field name
     *
     * @return string
     */
    public function getLevelFieldName();

    /**
     * Checks whether many trees can exist in one table
     *
     * @return bool
     */
    public function hasManyRoots();
    
    /**
     * Retrieves the Entity root_id field name.
     *
     * If cannot have many roots - returns null
     *
     * @return mixed
     */
    public function getRootIdFieldName();
}
