<?php

namespace DoctrineExtensions\Hierarchical;


interface NodeInterface
{
    public function __construct($entity, $hm);

    public function getDepth();
    public function getRoot();
    public function isRoot();
    public function isLeaf();
    public function unwrap();

    public function getSiblings();
    public function getFirstSibling();
    public function getLastSibling();
    public function getPrevSibling();
    public function getNextSibling();
    public function isSiblingOf($entity);

    public function getChildren();
    public function getFirstChild();
    public function getLastChild();
    public function isChildOf($entity);
    public function getNumberOfChildren();
    public function hasChildren();

    public function getDescendants();
    public function getNumberOfDescendants();
    public function isDescendantOf($entity);

    public function getAncestors();
    public function getParent();
    public function hasParent();

    public function insertAsChildOf($entity, $pos = null);
    public function insertAsLastChildOf($entity);
    public function insertAsFirstChildOf($entity);
    public function insertAsNextSiblingOf($entity);
    public function insertAsPrevSiblingOf($entity);

    public function moveAsChildOf($target, $pos = null);
    public function moveAsFirstChildOf($entity);
    public function moveAsLastChildOf($entity);
    public function moveAsNextSiblingOf($entity);
    public function moveAsPrevSiblingOf($entity);
}
