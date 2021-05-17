<?php

namespace Prokl\WordpressCi\FixtureGenerator\Entity;

/**
 * Interface EntityInterface
 * @package Prokl\WordpressCi\FixtureGenerator\Entity
 */
interface EntityInterface
{
    /**
     * Set current entity ID.
     *
     * @param int $id
     */
    public function setCurrentId(int $id) : void;

    /**
     * Check if entity.
     *
     * @param integer $id
     *
     * @return boolean
     */
    public function exists(int $id) : bool;

    /**
     * Create object.
     *
     * @return integer Database ID
     */
    public function create() : int;

    /**
     * Persist object.
     *
     * @return integer
     */
    public function persist() : int;

    /**
     * Delete fixtures.
     */
    public static function delete() : int;
}
